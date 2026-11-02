<?php

namespace Tests\Feature;

use App\Domain\Organizations\DemoOrganizationImporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class OrganizationImportTest extends TestCase
{
    use RefreshDatabase;

    private function importer(): DemoOrganizationImporter
    {
        return app(DemoOrganizationImporter::class);
    }

    public function test_reimport_keeps_identity_and_creates_no_duplicate_versions(): void
    {
        $this->seed();
        $identities = DB::table('external_identifiers')->pluck('entity_id', 'identifier_value')->all();
        $this->travel(1)->hour();
        $this->seed();

        $this->assertSame($identities, DB::table('external_identifiers')->pluck('entity_id', 'identifier_value')->all());
        foreach (['entities', 'organizations', 'external_identifiers', 'source_records', 'source_record_versions', 'entity_source_links'] as $table) {
            $this->assertDatabaseCount($table, 3);
        }
        $this->assertDatabaseCount('sources', 1);
        $this->assertTrue(DB::table('source_records')->whereColumn('last_seen_at', '>', 'first_seen_at')->count() === 3);
    }

    public function test_changed_payload_keeps_original_version_and_entity_identity(): void
    {
        $examples = DatabaseSeeder::examples();
        $this->importer()->import([$examples[0]]);
        $entityId = DB::table('entities')->value('id');
        $examples[0]['official_name'] = 'Gemeente Nieuwe Voorbeeldstad';
        $this->travel(1)->day();
        $this->importer()->import([$examples[0]]);

        $this->assertDatabaseCount('entities', 1);
        $this->assertDatabaseCount('source_record_versions', 2);
        $this->assertDatabaseHas('organizations', ['entity_id' => $entityId, 'official_name' => 'Gemeente Nieuwe Voorbeeldstad']);
        $versions = DB::table('source_record_versions')->orderBy('version_number')->get();
        $this->assertSame('Gemeente Voorbeeldstad', json_decode($versions[0]->payload, true)['official_name']);
        $this->assertSame('Gemeente Nieuwe Voorbeeldstad', json_decode($versions[1]->payload, true)['official_name']);
        $this->assertSame($versions[1]->id, DB::table('source_records')->value('current_version_id'));
    }

    public function test_equal_names_with_distinct_external_ids_are_never_merged(): void
    {
        $records = array_slice(DatabaseSeeder::examples(), 0, 2);
        $records[1]['official_name'] = $records[0]['official_name'];
        $this->importer()->import($records);
        $this->assertDatabaseCount('entities', 2);
        $this->assertSame(2, DB::table('external_identifiers')->distinct()->count('entity_id'));
    }

    public function test_partial_batch_does_not_remove_or_withdraw_unseen_organizations(): void
    {
        $this->seed();
        $this->importer()->import([DatabaseSeeder::examples()[0]]);
        $this->assertDatabaseCount('entities', 3);
        $this->assertSame(3, DB::table('source_records')->where('record_status', 'active')->count());
    }

    public function test_invalid_batch_is_rejected_without_partial_changes(): void
    {
        $records = DatabaseSeeder::examples();
        $records[1]['official_name'] = '   ';
        try {
            $this->importer()->import($records);
            $this->fail('Invalid records must not be imported.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sources', 0);
            $this->assertDatabaseCount('entities', 0);
        }
    }

    public function test_database_failure_rolls_back_the_entire_import(): void
    {
        DB::statement("ALTER TABLE organizations ADD CONSTRAINT test_rejected_type CHECK (organization_type <> 'Rejected')");
        $records = DatabaseSeeder::examples();
        $records[1]['organization_type'] = 'Rejected';
        try {
            $this->importer()->import($records);
            $this->fail('Database constraint must reject this batch.');
        } catch (QueryException) {
            $this->assertDatabaseCount('entities', 0);
            $this->assertDatabaseCount('source_records', 0);
            $this->assertDatabaseCount('sources', 0);
        }
    }

    public function test_demo_import_is_refused_in_production(): void
    {
        app()->instance('env', 'production');
        try {
            $this->importer()->import(DatabaseSeeder::examples());
            $this->fail('Production must never import fixtures.');
        } catch (LogicException) {
            $this->assertDatabaseCount('entities', 0);
        } finally {
            app()->instance('env', 'testing');
        }
    }

    public function test_database_rejects_an_identifier_assigned_to_another_entity(): void
    {
        $this->seed();
        $identifier = (array) DB::table('external_identifiers')->first();
        $identifier['id'] = (string) Str::uuid();
        $identifier['entity_id'] = DB::table('entities')->where('id', '!=', $identifier['entity_id'])->value('id');
        $this->expectException(QueryException::class);
        DB::table('external_identifiers')->insert($identifier);
    }

    public function test_database_rejects_duplicate_provenance_links(): void
    {
        $this->seed();
        $link = (array) DB::table('entity_source_links')->first();
        $link['id'] = (string) Str::uuid();
        $this->expectException(QueryException::class);
        DB::table('entity_source_links')->insert($link);
    }

    public function test_database_rejects_provenance_for_a_nonexistent_entity(): void
    {
        $this->seed();
        $link = (array) DB::table('entity_source_links')->first();
        $link['id'] = (string) Str::uuid();
        $link['entity_id'] = (string) Str::uuid();
        $this->expectException(QueryException::class);
        DB::table('entity_source_links')->insert($link);
    }

    public function test_database_rejects_invalid_validity_dates(): void
    {
        $this->seed();
        $this->expectException(QueryException::class);
        DB::table('entities')->update(['valid_from' => '2026-11-02T00:00:00Z', 'valid_until' => '2026-11-01T00:00:00Z']);
    }

    public function test_current_version_must_belong_to_the_same_source_record(): void
    {
        $this->seed();
        $records = DB::table('source_records')->get();
        $this->expectException(QueryException::class);
        DB::table('source_records')->where('id', $records[0]->id)->update(['current_version_id' => $records[1]->current_version_id]);
    }
}
