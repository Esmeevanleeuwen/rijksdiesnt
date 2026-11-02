<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_returns_ordered_organizations_and_explicit_demo_provenance(): void
    {
        $this->seed();
        $response = $this->getJson('/api/v1/organizations')->assertOk()
            ->assertJsonPath('meta.total', 3)->assertJsonPath('meta.data_mode', 'demo')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.official_name', 'Bureau Publieke Informatie')
            ->assertJsonPath('data.0.provenance.0.is_demo', true)
            ->assertJsonPath('data.0.provenance.0.url', null);
        $this->assertNotEmpty($response->json('data.0.provenance.0.external_id'));
        $this->assertNotEmpty($response->json('data.0.provenance.0.retrieved_at'));
    }

    public function test_search_is_case_insensitive_and_filters_by_type(): void
    {
        $this->seed();
        $this->getJson('/api/v1/organizations?q=VOORBEELDSTAD')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.official_name', 'Gemeente Voorbeeldstad');
        $this->getJson('/api/v1/organizations?type=Kennisinstituut')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.official_name', 'Instituut voor Omgevingsdata');
        $this->getJson('/api/v1/organizations?q=onvindbaar')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/organizations?q=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_detail_contains_identity_and_source_reference(): void
    {
        $this->seed();
        $id = DB::table('organizations')->where('official_name', 'Gemeente Voorbeeldstad')->value('entity_id');
        $this->getJson('/api/v1/organizations/'.$id)->assertOk()
            ->assertJsonPath('data.id', $id)->assertJsonPath('data.provenance.0.external_id', 'demo-gemeente-001')
            ->assertJsonPath('meta.data_mode', 'demo');
    }

    public function test_missing_or_malformed_organization_id_returns_json_404(): void
    {
        $this->getJson('/api/v1/organizations/'.Str::uuid())->assertNotFound();
        $this->getJson('/api/v1/organizations/not-a-uuid')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_pagination_is_bounded_and_invalid_filters_are_rejected(): void
    {
        $this->seed();
        $this->getJson('/api/v1/organizations?per_page=1&page=2')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.official_name', 'Gemeente Voorbeeldstad');
        $this->getJson('/api/v1/organizations?per_page=101')->assertUnprocessable();
        $this->getJson('/api/v1/organizations?page=0')->assertUnprocessable();
        $this->getJson('/api/v1/organizations?q='.str_repeat('a', 201))->assertUnprocessable();
        $this->getJson('/api/v1/organizations?q[]=unexpected')->assertUnprocessable();
    }

    public function test_withdrawn_entities_and_unverified_links_are_not_published(): void
    {
        $this->seed();
        $id = DB::table('organizations')->where('official_name', 'Gemeente Voorbeeldstad')->value('entity_id');
        DB::table('entities')->where('id', $id)->update(['lifecycle_status' => 'withdrawn']);
        $this->getJson('/api/v1/organizations/'.$id)->assertNotFound();
        DB::table('entity_source_links')->update(['status' => 'review']);
        $this->getJson('/api/v1/organizations')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_records_without_a_current_raw_version_are_not_published(): void
    {
        $this->seed();
        DB::table('source_records')->update(['current_version_id' => null]);
        $this->getJson('/api/v1/organizations')->assertOk()->assertJsonPath('meta.total', 0);
    }
}
