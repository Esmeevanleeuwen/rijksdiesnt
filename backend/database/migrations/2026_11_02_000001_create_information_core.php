<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type', 40);
            $table->string('lifecycle_status', 24)->default('active');
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_until')->nullable();
            $table->timestampsTz();
            $table->unique(['id', 'entity_type']);
        });
        DB::statement("ALTER TABLE entities ADD CONSTRAINT entities_type_check CHECK (entity_type = 'organization')");
        DB::statement("ALTER TABLE entities ADD CONSTRAINT entities_status_check CHECK (lifecycle_status IN ('active', 'superseded', 'withdrawn'))");
        DB::statement('ALTER TABLE entities ADD CONSTRAINT entities_validity_check CHECK (valid_until IS NULL OR valid_from IS NULL OR valid_until >= valid_from)');

        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('entity_id')->primary();
            $table->string('entity_type', 40)->default('organization');
            $table->text('official_name');
            $table->text('display_name')->nullable();
            $table->string('organization_type', 80)->nullable();
            $table->text('description')->nullable();
            $table->text('website_url')->nullable();
            $table->foreign(['entity_id', 'entity_type'])->references(['id', 'entity_type'])->on('entities')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE organizations ADD CONSTRAINT organizations_type_check CHECK (entity_type = 'organization')");
        DB::statement("ALTER TABLE organizations ADD CONSTRAINT organizations_name_check CHECK (official_name ~ '[^[:space:]]')");

        Schema::create('sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('name');
            $table->string('source_type', 24);
            $table->text('publisher_name');
            $table->text('base_url')->nullable();
            $table->text('license_code')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->timestampsTz();
        });

        Schema::create('source_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_id')->constrained('sources')->restrictOnDelete();
            $table->text('external_record_id');
            $table->string('record_type', 40);
            $table->string('record_status', 24)->default('active');
            $table->timestampTz('first_seen_at');
            $table->timestampTz('last_seen_at');
            $table->uuid('current_version_id')->nullable();
            $table->unique(['source_id', 'external_record_id']);
        });
        DB::statement('ALTER TABLE source_records ADD CONSTRAINT source_records_seen_check CHECK (last_seen_at >= first_seen_at)');
        DB::statement("ALTER TABLE source_records ADD CONSTRAINT source_records_status_check CHECK (record_status IN ('active', 'missing', 'withdrawn'))");
        DB::statement("ALTER TABLE source_records ADD CONSTRAINT source_records_external_id_check CHECK (external_record_id ~ '[^[:space:]]')");

        Schema::create('source_record_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_record_id')->constrained('source_records')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->char('content_hash', 64);
            $table->jsonb('payload');
            $table->timestampTz('recorded_at');
            $table->unique(['source_record_id', 'version_number']);
            $table->unique(['id', 'source_record_id']);
        });
        DB::statement('ALTER TABLE source_record_versions ADD CONSTRAINT source_record_versions_number_check CHECK (version_number > 0)');
        Schema::table('source_records', function (Blueprint $table) {
            $table->foreign(['current_version_id', 'id'], 'source_records_current_version_fk')
                ->references(['id', 'source_record_id'])->on('source_record_versions')->restrictOnDelete();
        });

        Schema::create('external_identifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained('entities')->restrictOnDelete();
            $table->foreignUuid('source_id')->constrained('sources')->restrictOnDelete();
            $table->text('identifier_scheme');
            $table->text('identifier_value');
            $table->boolean('is_primary')->default(false);
            // Temporal reuse is deliberately unsupported in this first version.
            $table->unique(['source_id', 'identifier_scheme', 'identifier_value'], 'external_identifiers_identity_unique');
        });
        DB::statement("ALTER TABLE external_identifiers ADD CONSTRAINT external_identifiers_value_check CHECK (identifier_value ~ '[^[:space:]]' AND identifier_scheme ~ '[^[:space:]]')");

        Schema::create('entity_source_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained('entities')->restrictOnDelete();
            $table->foreignUuid('source_record_id')->constrained('source_records')->restrictOnDelete();
            $table->string('link_type', 24)->default('primary');
            $table->string('status', 24)->default('active');
            $table->timestampTz('first_linked_at');
            $table->timestampTz('last_verified_at');
            $table->unique(['entity_id', 'source_record_id']);
        });
        DB::statement("ALTER TABLE entity_source_links ADD CONSTRAINT entity_source_links_type_check CHECK (link_type IN ('primary', 'corroborating', 'historical'))");
        DB::statement("ALTER TABLE entity_source_links ADD CONSTRAINT entity_source_links_status_check CHECK (status IN ('active', 'review', 'withdrawn'))");
        DB::statement('ALTER TABLE entity_source_links ADD CONSTRAINT entity_source_links_verified_check CHECK (last_verified_at >= first_linked_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_source_links');
        Schema::dropIfExists('external_identifiers');
        Schema::table('source_records', fn (Blueprint $table) => $table->dropForeign('source_records_current_version_fk'));
        Schema::dropIfExists('source_record_versions');
        Schema::dropIfExists('source_records');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('entities');
    }
};
