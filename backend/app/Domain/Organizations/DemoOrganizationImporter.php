<?php

namespace App\Domain\Organizations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LogicException;

/** Local fixtures only: this is not a connector or mapping for the ROO. */
final class DemoOrganizationImporter
{
    public const SOURCE_ID = 'd0e00000-0000-4000-8000-000000000001';

    public function import(array $records): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Demo-import is uitgeschakeld in productie.');
        }

        Validator::make(['records' => $records], [
            'records' => ['required', 'array', 'min:1', 'max:100'],
            'records.*' => ['required', 'array:external_id,official_name,display_name,organization_type,description,website_url'],
            'records.*.external_id' => ['required', 'string', 'max:120', 'distinct:strict', 'regex:/\S/'],
            'records.*.official_name' => ['required', 'string', 'max:300', 'regex:/\S/'],
            'records.*.display_name' => ['nullable', 'string', 'max:300'],
            'records.*.organization_type' => ['required', 'string', 'max:80'],
            'records.*.description' => ['required', 'string', 'max:2000'],
            'records.*.website_url' => ['nullable', 'url:http,https', 'max:2000'],
        ])->validate();

        DB::transaction(function () use ($records): void {
            $now = now();
            DB::table('sources')->insertOrIgnore([
                'id' => self::SOURCE_ID,
                'name' => 'Rijksdienst — lokale voorbeeldgegevens',
                'source_type' => 'file',
                'publisher_name' => 'Rijksdienst prototype',
                'base_url' => null,
                'license_code' => null,
                'is_enabled' => false,
                'is_demo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            // Serialize imports of this source, including simultaneous initial imports.
            $source = DB::table('sources')->where('id', self::SOURCE_ID)->lockForUpdate()->first();
            if (! $source->is_demo) {
                throw new LogicException('De gereserveerde demobron is geen demobron.');
            }

            foreach ($records as $payload) {
                $record = DB::table('source_records')->where('source_id', self::SOURCE_ID)
                    ->where('external_record_id', $payload['external_id'])->first();
                $identifier = DB::table('external_identifiers')->where('source_id', self::SOURCE_ID)
                    ->where('identifier_scheme', 'demo-organization')->where('identifier_value', $payload['external_id'])->first();
                $entityId = $identifier?->entity_id ?? (string) Str::uuid();

                if (! $identifier) {
                    DB::table('entities')->insert([
                        'id' => $entityId, 'entity_type' => 'organization', 'lifecycle_status' => 'active',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    DB::table('external_identifiers')->insert([
                        'id' => (string) Str::uuid(), 'entity_id' => $entityId, 'source_id' => self::SOURCE_ID,
                        'identifier_scheme' => 'demo-organization', 'identifier_value' => $payload['external_id'], 'is_primary' => true,
                    ]);
                }

                $recordId = $record?->id ?? (string) Str::uuid();
                if (! $record) {
                    DB::table('source_records')->insert([
                        'id' => $recordId, 'source_id' => self::SOURCE_ID, 'external_record_id' => $payload['external_id'],
                        'record_type' => 'organization', 'record_status' => 'active', 'first_seen_at' => $now, 'last_seen_at' => $now,
                    ]);
                }

                $raw = $payload;
                ksort($raw);
                $json = json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $hash = hash('sha256', $json);
                $currentVersion = $record?->current_version_id
                    ? DB::table('source_record_versions')->where('id', $record->current_version_id)->first() : null;
                if (! $currentVersion || $currentVersion->content_hash !== $hash) {
                    $versionId = (string) Str::uuid();
                    DB::table('source_record_versions')->insert([
                        'id' => $versionId, 'source_record_id' => $recordId,
                        'version_number' => ($currentVersion?->version_number ?? 0) + 1,
                        'content_hash' => $hash, 'payload' => $json, 'recorded_at' => $now,
                    ]);
                    DB::table('source_records')->where('id', $recordId)->update(['current_version_id' => $versionId]);
                    DB::table('organizations')->updateOrInsert(['entity_id' => $entityId], [
                        'entity_type' => 'organization', 'official_name' => $payload['official_name'],
                        'display_name' => $payload['display_name'] ?? null, 'organization_type' => $payload['organization_type'],
                        'description' => $payload['description'], 'website_url' => $payload['website_url'] ?? null,
                    ]);
                    DB::table('entities')->where('id', $entityId)->update(['updated_at' => $now]);
                }

                DB::table('source_records')->where('id', $recordId)->update(['last_seen_at' => $now]);
                $link = DB::table('entity_source_links')->where('entity_id', $entityId)->where('source_record_id', $recordId)->first();
                if ($link) {
                    DB::table('entity_source_links')->where('id', $link->id)->update(['last_verified_at' => $now]);
                } else {
                    DB::table('entity_source_links')->insert([
                        'id' => (string) Str::uuid(), 'entity_id' => $entityId, 'source_record_id' => $recordId,
                        'link_type' => 'primary', 'status' => 'active', 'first_linked_at' => $now, 'last_verified_at' => $now,
                    ]);
                }
            }
            // Missing from a batch is not evidence of withdrawal; never delete it here.
        });
    }
}
