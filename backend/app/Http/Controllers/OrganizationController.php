<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'string', 'max:80'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = $this->published();
        $term = trim($filters['q'] ?? '');
        if ($term !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
            $query->where(function (Builder $query) use ($pattern): void {
                $query->whereRaw("o.official_name ILIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("o.description ILIKE ? ESCAPE '!'", [$pattern]);
            });
        }
        if (! empty($filters['type'])) {
            $query->where('o.organization_type', $filters['type']);
        }
        $total = (clone $query)->count();
        $perPage = (int) ($filters['per_page'] ?? 24);
        $page = (int) ($filters['page'] ?? 1);
        $rows = $query->orderBy('o.official_name')->orderBy('e.id')->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'data' => $rows->map(fn (object $row) => $this->present($row)),
            'meta' => ['total' => $total, 'data_mode' => 'demo', 'page' => $page, 'per_page' => $perPage],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $organization = $this->published()->where('e.id', $id)->first();
        abort_unless($organization, 404);

        return response()->json(['data' => $this->present($organization), 'meta' => ['data_mode' => 'demo']]);
    }

    private function published(): Builder
    {
        return DB::table('organizations as o')->join('entities as e', 'e.id', '=', 'o.entity_id')
            ->where('e.lifecycle_status', 'active')->where('e.entity_type', 'organization')
            ->whereExists(function (Builder $query): void {
                $query->selectRaw('1')->from('entity_source_links as l')
                    ->join('source_records as r', 'r.id', '=', 'l.source_record_id')
                    ->join('sources as s', 's.id', '=', 'r.source_id')
                    ->whereColumn('l.entity_id', 'e.id')->where('l.status', 'active')
                    ->where('r.record_status', 'active')->whereNotNull('r.current_version_id')->where('s.is_demo', true);
            })
            ->select(['e.id', 'o.official_name', 'o.display_name', 'o.organization_type', 'o.description', 'o.website_url']);
    }

    private function present(object $organization): array
    {
        $sources = DB::table('entity_source_links as l')
            ->join('source_records as r', 'r.id', '=', 'l.source_record_id')
            ->join('sources as s', 's.id', '=', 'r.source_id')
            ->where('l.entity_id', $organization->id)->where('l.status', 'active')
            ->where('r.record_status', 'active')->whereNotNull('r.current_version_id')->where('s.is_demo', true)
            ->orderBy('s.name')->orderBy('r.external_record_id')
            ->get(['s.name', 's.base_url', 's.is_demo', 'r.external_record_id', 'r.last_seen_at']);

        return [
            'id' => $organization->id,
            'official_name' => $organization->official_name,
            'display_name' => $organization->display_name,
            'organization_type' => $organization->organization_type,
            'description' => $organization->description,
            'website_url' => $organization->website_url,
            'provenance' => $sources->map(fn (object $source) => [
                'name' => $source->name,
                'url' => $source->base_url,
                'external_id' => $source->external_record_id,
                'retrieved_at' => Carbon::parse($source->last_seen_at)->toIso8601String(),
                'is_demo' => (bool) $source->is_demo,
            ])->all(),
        ];
    }
}
