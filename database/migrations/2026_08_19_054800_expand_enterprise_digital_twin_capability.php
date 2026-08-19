<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_entities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->string('stable_key', 160);
            $table->string('entity_type', 32);
            $table->string('lifecycle_state', 24)->default('ACTIVE');
            $table->string('name_ar', 240);
            $table->string('name_en', 240)->nullable();
            $table->jsonb('properties');
            $table->jsonb('revision_provenance');
            $table->timestampsTz();

            $table->unique(['enterprise_id', 'stable_key'], 'enterprise_entity_stable_key_unique');
            $table->foreign('enterprise_id', 'enterprise_entity_enterprise_fk')
                ->references('id')
                ->on('simulation_enterprises')
                ->restrictOnDelete();
        });

        Schema::create('enterprise_relationships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('source_entity_id');
            $table->uuid('target_entity_id');
            $table->string('relationship_type', 40);
            $table->jsonb('properties');
            $table->timestampsTz();

            $table->unique(
                ['source_entity_id', 'target_entity_id', 'relationship_type'],
                'enterprise_relationship_identity_unique',
            );
            $table->foreign('enterprise_id', 'enterprise_relationship_enterprise_fk')
                ->references('id')
                ->on('simulation_enterprises')
                ->restrictOnDelete();
            $table->foreign('source_entity_id', 'enterprise_relationship_source_fk')
                ->references('id')
                ->on('enterprise_entities')
                ->restrictOnDelete();
            $table->foreign('target_entity_id', 'enterprise_relationship_target_fk')
                ->references('id')
                ->on('enterprise_entities')
                ->restrictOnDelete();
        });

        Schema::create('enterprise_digital_twins', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->string('slug', 140);
            $table->string('name_ar', 240);
            $table->string('name_en', 240)->nullable();
            $table->text('purpose')->nullable();
            $table->string('lifecycle_state', 24)->default('ACTIVE');
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();

            $table->unique(['enterprise_id', 'slug'], 'enterprise_twin_slug_unique');
            $table->foreign('enterprise_id', 'enterprise_twin_enterprise_fk')
                ->references('id')
                ->on('simulation_enterprises')
                ->restrictOnDelete();
        });

        Schema::table('simulation_digital_twin_revisions', function (Blueprint $table): void {
            $table->uuid('digital_twin_id')->nullable();
            $table->uuid('source_revision_id')->nullable();
            $table->jsonb('simulation_local_objects')->nullable();
            $table->jsonb('validation_report')->nullable();
            $table->timestampTz('validated_at')->nullable();

            $table->foreign('digital_twin_id', 'sim_twin_identity_fk')
                ->references('id')
                ->on('enterprise_digital_twins')
                ->restrictOnDelete();
            $table->foreign('source_revision_id', 'sim_twin_source_revision_fk')
                ->references('id')
                ->on('simulation_digital_twin_revisions')
                ->restrictOnDelete();
        });

        Schema::table('simulation_baselines', function (Blueprint $table): void {
            $table->uuid('digital_twin_id')->nullable();

            $table->foreign('digital_twin_id', 'sim_baseline_twin_identity_fk')
                ->references('id')
                ->on('enterprise_digital_twins')
                ->restrictOnDelete();
        });

        DB::statement(
            "ALTER TABLE enterprise_entities ADD CONSTRAINT enterprise_entity_type_check CHECK (entity_type IN ('SYSTEM','APPLICATION','SERVICE','NETWORK','DEVICE','IDENTITY','DATA','SECURITY_CONTROL','TEAM','ROLE'))",
        );
        DB::statement(
            "ALTER TABLE enterprise_entities ADD CONSTRAINT enterprise_entity_lifecycle_check CHECK (lifecycle_state IN ('ACTIVE','RETIRED'))",
        );
        DB::statement(
            "ALTER TABLE enterprise_relationships ADD CONSTRAINT enterprise_relationship_type_check CHECK (relationship_type IN ('HOSTS','DEPENDS_ON','AUTHENTICATES_WITH','CONNECTS_TO','MANAGED_BY','PROTECTED_BY','STORES','ROUTES_TO','MEMBER_OF'))",
        );
        DB::statement(
            "ALTER TABLE enterprise_digital_twins ADD CONSTRAINT enterprise_twin_lifecycle_check CHECK (lifecycle_state IN ('ACTIVE','ARCHIVED'))",
        );

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_published_twin_revision_mutation() RETURNS trigger AS $$
BEGIN
    IF OLD.status = 'PUBLISHED' THEN
        RAISE EXCEPTION 'published Digital Twin revisions are immutable' USING ERRCODE = '55000';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER simulation_digital_twin_revisions_immutable
BEFORE UPDATE OR DELETE ON simulation_digital_twin_revisions
FOR EACH ROW EXECUTE FUNCTION prevent_published_twin_revision_mutation();

CREATE OR REPLACE FUNCTION prevent_published_simulation_baseline_mutation() RETURNS trigger AS $$
BEGIN
    IF OLD.status = 'PUBLISHED' THEN
        RAISE EXCEPTION 'published Simulation baselines are immutable' USING ERRCODE = '55000';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER simulation_baselines_immutable
BEFORE UPDATE OR DELETE ON simulation_baselines
FOR EACH ROW EXECUTE FUNCTION prevent_published_simulation_baseline_mutation();
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS simulation_baselines_immutable ON simulation_baselines');
        DB::statement('DROP FUNCTION IF EXISTS prevent_published_simulation_baseline_mutation()');
        DB::statement('DROP TRIGGER IF EXISTS simulation_digital_twin_revisions_immutable ON simulation_digital_twin_revisions');
        DB::statement('DROP FUNCTION IF EXISTS prevent_published_twin_revision_mutation()');

        Schema::table('simulation_baselines', function (Blueprint $table): void {
            $table->dropForeign('sim_baseline_twin_identity_fk');
            $table->dropColumn('digital_twin_id');
        });

        Schema::table('simulation_digital_twin_revisions', function (Blueprint $table): void {
            $table->dropForeign('sim_twin_source_revision_fk');
            $table->dropForeign('sim_twin_identity_fk');
            $table->dropColumn([
                'digital_twin_id',
                'source_revision_id',
                'simulation_local_objects',
                'validation_report',
                'validated_at',
            ]);
        });

        Schema::dropIfExists('enterprise_relationships');
        Schema::dropIfExists('enterprise_entities');
        Schema::dropIfExists('enterprise_digital_twins');
    }
};
