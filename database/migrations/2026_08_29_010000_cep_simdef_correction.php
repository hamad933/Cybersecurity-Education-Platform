<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->createEnterpriseDefinitionTables();
        $this->strengthenDigitalTwinOwnershipAndLifecycle();
        $this->createDigitalTwinDefinitionTables();
        $this->correctLabDefinitionLifecycleAndBinding();
        $this->createLabDefinitionTables();
        $this->installDefinitionImmutabilityTriggers();
    }

    private function createEnterpriseDefinitionTables(): void
    {
        Schema::create('simulation_enterprise_entities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->string('entity_key', 160);
            $table->string('entity_type', 48);
            $table->string('name_ar', 240);
            $table->string('name_en', 240)->nullable();
            $table->string('lifecycle_state', 32)->default('ACTIVE');
            $table->jsonb('properties');
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(['enterprise_id', 'entity_key'], 'sim_ent_entity_key_unique');
            $table->unique(['enterprise_id', 'id'], 'sim_ent_entity_owner_unique');
            $table->foreign('enterprise_id', 'sim_ent_entity_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
        });

        Schema::create('simulation_enterprise_relationships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('source_entity_id');
            $table->uuid('target_entity_id');
            $table->string('relationship_type', 48);
            $table->jsonb('properties');
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(
                ['enterprise_id', 'source_entity_id', 'target_entity_id', 'relationship_type'],
                'sim_ent_relationship_unique',
            );
            $table->foreign('enterprise_id', 'sim_ent_rel_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
            $table->foreign(['enterprise_id', 'source_entity_id'], 'sim_ent_rel_source_fk')->references(['enterprise_id', 'id'])->on('simulation_enterprise_entities')->restrictOnDelete();
            $table->foreign(['enterprise_id', 'target_entity_id'], 'sim_ent_rel_target_fk')->references(['enterprise_id', 'id'])->on('simulation_enterprise_entities')->restrictOnDelete();
        });

        Schema::create('simulation_device_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->string('template_key', 160);
            $table->string('device_type', 80);
            $table->string('name_ar', 240);
            $table->string('name_en', 240)->nullable();
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(['enterprise_id', 'template_key'], 'sim_device_template_key_unique');
            $table->unique(['enterprise_id', 'id'], 'sim_device_template_owner_unique');
            $table->foreign('enterprise_id', 'sim_device_template_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
        });

        Schema::create('simulation_device_template_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('device_template_id');
            $table->uuid('based_on_revision_id')->nullable();
            $table->unsignedInteger('revision');
            $table->string('status', 24);
            $table->jsonb('capabilities');
            $table->jsonb('state_model');
            $table->jsonb('actions');
            $table->jsonb('events');
            $table->jsonb('telemetry');
            $table->jsonb('behavior_rules');
            $table->jsonb('validation_hooks');
            $table->jsonb('validation_report')->nullable();
            $table->char('digest', 64);
            $table->timestampTz('validated_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(['device_template_id', 'revision'], 'sim_device_template_revision_unique');
            $table->unique(['enterprise_id', 'id'], 'sim_device_revision_owner_unique');
            $table->foreign(['enterprise_id', 'device_template_id'], 'sim_device_revision_template_fk')->references(['enterprise_id', 'id'])->on('simulation_device_templates')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE simulation_device_template_revisions ADD CONSTRAINT sim_device_revision_parent_fk FOREIGN KEY (based_on_revision_id) REFERENCES simulation_device_template_revisions (id) ON DELETE RESTRICT');
        DB::statement("ALTER TABLE simulation_enterprise_entities ADD CONSTRAINT sim_ent_entity_type_check CHECK (entity_type IN ('SYSTEM','APPLICATION','SERVICE','NETWORK','DEVICE','IDENTITY','DATA','SECURITY_CONTROL','ROLE','TEAM'))");
        DB::statement("ALTER TABLE simulation_enterprise_entities ADD CONSTRAINT sim_ent_entity_lifecycle_check CHECK (lifecycle_state IN ('ACTIVE','INACTIVE','RETIRED'))");
        DB::statement("ALTER TABLE simulation_enterprise_relationships ADD CONSTRAINT sim_ent_relationship_type_check CHECK (relationship_type IN ('HOSTS','DEPENDS_ON','AUTHENTICATES_WITH','CONNECTS_TO','MANAGED_BY','PROTECTED_BY','STORES','ROUTES_TO','MEMBER_OF'))");
        DB::statement("ALTER TABLE simulation_device_template_revisions ADD CONSTRAINT sim_device_revision_status_check CHECK (status IN ('DRAFT','VALIDATED','PUBLISHED'))");
        DB::statement("ALTER TABLE simulation_device_template_revisions ADD CONSTRAINT sim_device_revision_lifecycle_check CHECK ((status = 'DRAFT' AND validated_at IS NULL AND published_at IS NULL) OR (status = 'VALIDATED' AND validated_at IS NOT NULL AND published_at IS NULL AND validation_report ->> 'valid' = 'true') OR (status = 'PUBLISHED' AND validated_at IS NOT NULL AND published_at IS NOT NULL AND validation_report ->> 'valid' = 'true'))");
    }

    private function strengthenDigitalTwinOwnershipAndLifecycle(): void
    {
        Schema::table('simulation_digital_twin_revisions', function (Blueprint $table): void {
            $table->uuid('based_on_revision_id')->nullable();
            $table->jsonb('validation_report')->nullable();
            $table->timestampTz('validated_at')->nullable();
        });

        DB::statement('ALTER TABLE simulation_digital_twin_revisions DISABLE TRIGGER simulation_twin_revisions_immutable');
        DB::statement(<<<'SQL'
UPDATE simulation_digital_twin_revisions
SET validated_at = COALESCE(published_at, updated_at, created_at),
    validation_report = '{"valid":true,"source":"pre-correction-published-revision"}'::jsonb
WHERE status = 'PUBLISHED'
SQL);
        DB::statement('ALTER TABLE simulation_digital_twin_revisions ENABLE TRIGGER simulation_twin_revisions_immutable');

        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT sim_twin_status_check');
        DB::statement("ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_status_check CHECK (status IN ('DRAFT','VALIDATED','PUBLISHED'))");
        DB::statement("ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_lifecycle_check CHECK ((status = 'DRAFT' AND validated_at IS NULL AND published_at IS NULL) OR (status = 'VALIDATED' AND validated_at IS NOT NULL AND published_at IS NULL AND validation_report ->> 'valid' = 'true') OR (status = 'PUBLISHED' AND validated_at IS NOT NULL AND published_at IS NOT NULL AND validation_report ->> 'valid' = 'true'))");
        DB::statement('ALTER TABLE simulation_digital_twins ADD CONSTRAINT sim_twin_enterprise_identity_unique UNIQUE (enterprise_id, id)');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_revision_enterprise_id_unique UNIQUE (enterprise_id, id)');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_revision_lineage_unique UNIQUE (enterprise_id, digital_twin_id, id)');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_revision_owner_fk FOREIGN KEY (enterprise_id, digital_twin_id) REFERENCES simulation_digital_twins (enterprise_id, id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_revision_parent_fk FOREIGN KEY (based_on_revision_id) REFERENCES simulation_digital_twin_revisions (id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE simulation_baselines ADD CONSTRAINT sim_baseline_revision_owner_fk FOREIGN KEY (enterprise_id, digital_twin_id, digital_twin_revision_id) REFERENCES simulation_digital_twin_revisions (enterprise_id, digital_twin_id, id) ON DELETE RESTRICT');
    }

    private function createDigitalTwinDefinitionTables(): void
    {
        Schema::create('simulation_digital_twin_components', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('digital_twin_revision_id');
            $table->string('component_key', 160);
            $table->string('ownership_scope', 32);
            $table->uuid('enterprise_entity_id')->nullable();
            $table->uuid('device_template_revision_id')->nullable();
            $table->string('name_ar', 240);
            $table->jsonb('simulation_definition');
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(['digital_twin_revision_id', 'component_key'], 'sim_twin_component_key_unique');
            $table->unique(['digital_twin_revision_id', 'id'], 'sim_twin_component_revision_unique');
            $table->foreign(['enterprise_id', 'digital_twin_revision_id'], 'sim_twin_component_revision_fk')->references(['enterprise_id', 'id'])->on('simulation_digital_twin_revisions')->cascadeOnDelete();
            $table->foreign(['enterprise_id', 'enterprise_entity_id'], 'sim_twin_component_entity_fk')->references(['enterprise_id', 'id'])->on('simulation_enterprise_entities')->restrictOnDelete();
            $table->foreign(['enterprise_id', 'device_template_revision_id'], 'sim_twin_component_template_fk')->references(['enterprise_id', 'id'])->on('simulation_device_template_revisions')->restrictOnDelete();
        });

        Schema::create('simulation_digital_twin_relationships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('digital_twin_revision_id');
            $table->uuid('source_component_id');
            $table->uuid('target_component_id');
            $table->string('relationship_type', 48);
            $table->jsonb('properties');
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(
                ['digital_twin_revision_id', 'source_component_id', 'target_component_id', 'relationship_type'],
                'sim_twin_relationship_unique',
            );
            $table->foreign('digital_twin_revision_id', 'sim_twin_relationship_revision_fk')->references('id')->on('simulation_digital_twin_revisions')->cascadeOnDelete();
            $table->foreign(['digital_twin_revision_id', 'source_component_id'], 'sim_twin_relationship_source_fk')->references(['digital_twin_revision_id', 'id'])->on('simulation_digital_twin_components')->restrictOnDelete();
            $table->foreign(['digital_twin_revision_id', 'target_component_id'], 'sim_twin_relationship_target_fk')->references(['digital_twin_revision_id', 'id'])->on('simulation_digital_twin_components')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE simulation_digital_twin_components ADD CONSTRAINT sim_twin_component_scope_check CHECK ((ownership_scope = 'ENTERPRISE_ENTITY' AND enterprise_entity_id IS NOT NULL) OR (ownership_scope = 'SIMULATION_LOCAL' AND enterprise_entity_id IS NULL))");
        DB::statement("ALTER TABLE simulation_digital_twin_relationships ADD CONSTRAINT sim_twin_relationship_type_check CHECK (relationship_type IN ('HOSTS','DEPENDS_ON','AUTHENTICATES_WITH','CONNECTS_TO','MANAGED_BY','PROTECTED_BY','STORES','ROUTES_TO','MEMBER_OF'))");
    }

    private function correctLabDefinitionLifecycleAndBinding(): void
    {
        Schema::create('simulation_labs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug', 140)->unique();
            $table->string('title_ar', 240);
            $table->string('title_en', 240)->nullable();
            $table->string('created_by', 120)->nullable();
            $table->timestampsTz();
        });

        Schema::table('simulation_lab_definitions', function (Blueprint $table): void {
            $table->uuid('lab_id')->nullable();
            $table->uuid('based_on_revision_id')->nullable();
            $table->string('environment_binding_mode', 32)->nullable();
            $table->jsonb('environment_contract')->nullable();
            $table->jsonb('validation_report')->nullable();
            $table->timestampTz('validated_at')->nullable();
            $table->timestampTz('published_at')->nullable();
        });

        DB::statement('ALTER TABLE simulation_lab_definitions DISABLE TRIGGER simulation_labs_immutable');
        $labIdentities = DB::table('simulation_lab_definitions')
            ->select(['slug', 'title_ar', 'title_en', 'created_by', 'created_at', 'updated_at'])
            ->orderBy('revision')
            ->get()
            ->unique('slug');

        foreach ($labIdentities as $labIdentity) {
            $labId = (string) Str::uuid7();
            DB::table('simulation_labs')->insert([
                'id' => $labId,
                'slug' => $labIdentity->slug,
                'title_ar' => $labIdentity->title_ar,
                'title_en' => $labIdentity->title_en,
                'created_by' => $labIdentity->created_by,
                'created_at' => $labIdentity->created_at,
                'updated_at' => $labIdentity->updated_at,
            ]);
            DB::table('simulation_lab_definitions')->where('slug', $labIdentity->slug)->update(['lab_id' => $labId]);
        }

        DB::statement(<<<'SQL'
UPDATE simulation_lab_definitions
SET environment_binding_mode = 'ENTERPRISE_BASELINE',
    environment_contract = jsonb_build_object(
        'schema', 'cep.simulation.lab-environment-contract.v1',
        'execution_model', 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
        'required_capabilities', COALESCE(configuration -> 'required_capabilities', '[]'::jsonb)
    ),
    validated_at = CASE WHEN status = 'PUBLISHED' THEN COALESCE(updated_at, created_at) ELSE NULL END,
    published_at = CASE WHEN status = 'PUBLISHED' THEN COALESCE(updated_at, created_at) ELSE NULL END,
    validation_report = CASE WHEN status = 'PUBLISHED' THEN '{"valid":true,"source":"pre-correction-published-revision"}'::jsonb ELSE NULL END
SQL);
        DB::statement('ALTER TABLE simulation_lab_definitions ENABLE TRIGGER simulation_labs_immutable');

        DB::statement('ALTER TABLE simulation_lab_definitions ALTER COLUMN enterprise_id DROP NOT NULL');
        DB::statement('ALTER TABLE simulation_lab_definitions ALTER COLUMN baseline_id DROP NOT NULL');
        DB::statement('ALTER TABLE simulation_lab_definitions ALTER COLUMN lab_id SET NOT NULL');
        DB::statement('ALTER TABLE simulation_lab_definitions ALTER COLUMN environment_binding_mode SET NOT NULL');
        DB::statement('ALTER TABLE simulation_lab_definitions ALTER COLUMN environment_contract SET NOT NULL');
        DB::statement('ALTER TABLE simulation_lab_definitions DROP CONSTRAINT sim_lab_status_check');
        DB::statement("ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_status_check CHECK (status IN ('DRAFT','VALIDATED','PUBLISHED'))");
        DB::statement("ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_lifecycle_check CHECK ((status = 'DRAFT' AND validated_at IS NULL AND published_at IS NULL) OR (status = 'VALIDATED' AND validated_at IS NOT NULL AND published_at IS NULL AND validation_report ->> 'valid' = 'true') OR (status = 'PUBLISHED' AND validated_at IS NOT NULL AND published_at IS NOT NULL AND validation_report ->> 'valid' = 'true'))");
        DB::statement("ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_environment_binding_check CHECK ((environment_binding_mode = 'LAB_LOCAL' AND enterprise_id IS NULL AND baseline_id IS NULL) OR (environment_binding_mode = 'ENTERPRISE_BASELINE' AND enterprise_id IS NOT NULL AND baseline_id IS NOT NULL))");
        DB::statement('ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_identity_revision_unique UNIQUE (lab_id, revision)');
        DB::statement('ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_identity_fk FOREIGN KEY (lab_id) REFERENCES simulation_labs (id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_revision_parent_fk FOREIGN KEY (based_on_revision_id) REFERENCES simulation_lab_definitions (id) ON DELETE RESTRICT');
    }

    private function createLabDefinitionTables(): void
    {
        Schema::create('simulation_lab_task_nodes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('lab_definition_id');
            $table->string('task_key', 160);
            $table->string('title_ar', 240);
            $table->text('objective');
            $table->jsonb('permitted_tools');
            $table->jsonb('required_capabilities');
            $table->string('required_role', 120)->nullable();
            $table->jsonb('expected_signals');
            $table->jsonb('validation_rule');
            $table->decimal('completion_weight', 7, 4)->default(1);
            $table->boolean('is_optional')->default(false);
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(['lab_definition_id', 'task_key'], 'sim_lab_task_key_unique');
            $table->unique(['lab_definition_id', 'id'], 'sim_lab_task_revision_unique');
            $table->foreign('lab_definition_id', 'sim_lab_task_definition_fk')->references('id')->on('simulation_lab_definitions')->cascadeOnDelete();
        });

        Schema::create('simulation_lab_task_dependencies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('lab_definition_id');
            $table->uuid('predecessor_task_id');
            $table->uuid('successor_task_id');
            $table->string('dependency_type', 24)->default('REQUIRED');
            $table->jsonb('condition')->nullable();
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(
                ['lab_definition_id', 'predecessor_task_id', 'successor_task_id'],
                'sim_lab_task_dependency_unique',
            );
            $table->foreign('lab_definition_id', 'sim_lab_dependency_definition_fk')->references('id')->on('simulation_lab_definitions')->cascadeOnDelete();
            $table->foreign(['lab_definition_id', 'predecessor_task_id'], 'sim_lab_dependency_predecessor_fk')->references(['lab_definition_id', 'id'])->on('simulation_lab_task_nodes')->cascadeOnDelete();
            $table->foreign(['lab_definition_id', 'successor_task_id'], 'sim_lab_dependency_successor_fk')->references(['lab_definition_id', 'id'])->on('simulation_lab_task_nodes')->cascadeOnDelete();
        });

        Schema::create('simulation_lab_device_template_references', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('lab_definition_id');
            $table->uuid('device_template_revision_id');
            $table->string('reference_key', 160);
            $table->jsonb('required_capabilities');
            $table->jsonb('parameters');
            $table->string('created_by', 120);
            $table->timestampsTz();
            $table->unique(['lab_definition_id', 'reference_key'], 'sim_lab_template_reference_unique');
            $table->foreign('lab_definition_id', 'sim_lab_template_definition_fk')->references('id')->on('simulation_lab_definitions')->cascadeOnDelete();
            $table->foreign('device_template_revision_id', 'sim_lab_template_revision_fk')->references('id')->on('simulation_device_template_revisions')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE simulation_lab_task_nodes ADD CONSTRAINT sim_lab_task_weight_check CHECK (completion_weight > 0 AND completion_weight <= 100)');
        DB::statement("ALTER TABLE simulation_lab_task_dependencies ADD CONSTRAINT sim_lab_dependency_type_check CHECK (dependency_type IN ('REQUIRED','CONDITIONAL'))");
        DB::statement('ALTER TABLE simulation_lab_task_dependencies ADD CONSTRAINT sim_lab_dependency_not_self_check CHECK (predecessor_task_id <> successor_task_id)');
    }

    private function installDefinitionImmutabilityTriggers(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TRIGGER simulation_device_template_revisions_immutable
BEFORE UPDATE OR DELETE ON simulation_device_template_revisions
FOR EACH ROW EXECUTE FUNCTION prevent_published_simulation_definition_mutation();

CREATE OR REPLACE FUNCTION prevent_published_twin_child_mutation() RETURNS trigger AS $$
DECLARE
    parent_revision_id uuid;
BEGIN
    IF TG_OP = 'DELETE' THEN
        parent_revision_id := OLD.digital_twin_revision_id;
    ELSE
        parent_revision_id := NEW.digital_twin_revision_id;
    END IF;

    IF EXISTS (
        SELECT 1 FROM simulation_digital_twin_revisions
        WHERE id = parent_revision_id AND status = 'PUBLISHED'
    ) THEN
        RAISE EXCEPTION 'published Digital Twin revision children are immutable' USING ERRCODE = '55000';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER simulation_twin_components_immutable
BEFORE INSERT OR UPDATE OR DELETE ON simulation_digital_twin_components
FOR EACH ROW EXECUTE FUNCTION prevent_published_twin_child_mutation();
CREATE TRIGGER simulation_twin_relationships_immutable
BEFORE INSERT OR UPDATE OR DELETE ON simulation_digital_twin_relationships
FOR EACH ROW EXECUTE FUNCTION prevent_published_twin_child_mutation();

CREATE OR REPLACE FUNCTION prevent_published_lab_child_mutation() RETURNS trigger AS $$
DECLARE
    parent_revision_id uuid;
BEGIN
    IF TG_OP = 'DELETE' THEN
        parent_revision_id := OLD.lab_definition_id;
    ELSE
        parent_revision_id := NEW.lab_definition_id;
    END IF;

    IF EXISTS (
        SELECT 1 FROM simulation_lab_definitions
        WHERE id = parent_revision_id AND status = 'PUBLISHED'
    ) THEN
        RAISE EXCEPTION 'published Lab definition children are immutable' USING ERRCODE = '55000';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER simulation_lab_tasks_immutable
BEFORE INSERT OR UPDATE OR DELETE ON simulation_lab_task_nodes
FOR EACH ROW EXECUTE FUNCTION prevent_published_lab_child_mutation();
CREATE TRIGGER simulation_lab_dependencies_immutable
BEFORE INSERT OR UPDATE OR DELETE ON simulation_lab_task_dependencies
FOR EACH ROW EXECUTE FUNCTION prevent_published_lab_child_mutation();
CREATE TRIGGER simulation_lab_template_references_immutable
BEFORE INSERT OR UPDATE OR DELETE ON simulation_lab_device_template_references
FOR EACH ROW EXECUTE FUNCTION prevent_published_lab_child_mutation();
SQL);
    }

    public function down(): void
    {
        if (DB::table('simulation_lab_definitions')->where('status', 'VALIDATED')->exists()
            || DB::table('simulation_lab_definitions')->whereNull('enterprise_id')->exists()
            || DB::table('simulation_lab_definitions')->whereNull('baseline_id')->exists()) {
            throw new RuntimeException('Rollback requires removal or migration of corrected Lab drafts and lab-local definitions.');
        }
        if (DB::table('simulation_digital_twin_revisions')->where('status', 'VALIDATED')->exists()) {
            throw new RuntimeException('Rollback requires publication or removal of validated Digital Twin revisions.');
        }

        DB::statement('DROP TRIGGER IF EXISTS simulation_lab_template_references_immutable ON simulation_lab_device_template_references');
        DB::statement('DROP TRIGGER IF EXISTS simulation_lab_dependencies_immutable ON simulation_lab_task_dependencies');
        DB::statement('DROP TRIGGER IF EXISTS simulation_lab_tasks_immutable ON simulation_lab_task_nodes');
        DB::statement('DROP FUNCTION IF EXISTS prevent_published_lab_child_mutation()');
        DB::statement('DROP TRIGGER IF EXISTS simulation_twin_relationships_immutable ON simulation_digital_twin_relationships');
        DB::statement('DROP TRIGGER IF EXISTS simulation_twin_components_immutable ON simulation_digital_twin_components');
        DB::statement('DROP FUNCTION IF EXISTS prevent_published_twin_child_mutation()');
        DB::statement('DROP TRIGGER IF EXISTS simulation_device_template_revisions_immutable ON simulation_device_template_revisions');

        Schema::dropIfExists('simulation_lab_device_template_references');
        Schema::dropIfExists('simulation_lab_task_dependencies');
        Schema::dropIfExists('simulation_lab_task_nodes');

        DB::statement('ALTER TABLE simulation_lab_definitions DROP CONSTRAINT IF EXISTS sim_lab_revision_parent_fk');
        DB::statement('ALTER TABLE simulation_lab_definitions DROP CONSTRAINT IF EXISTS sim_lab_identity_fk');
        DB::statement('ALTER TABLE simulation_lab_definitions DROP CONSTRAINT IF EXISTS sim_lab_identity_revision_unique');
        DB::statement('ALTER TABLE simulation_lab_definitions DROP CONSTRAINT IF EXISTS sim_lab_environment_binding_check');
        DB::statement('ALTER TABLE simulation_lab_definitions DROP CONSTRAINT IF EXISTS sim_lab_lifecycle_check');
        DB::statement('ALTER TABLE simulation_lab_definitions DROP CONSTRAINT IF EXISTS sim_lab_status_check');
        DB::statement("ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_status_check CHECK (status IN ('DRAFT','PUBLISHED'))");
        Schema::table('simulation_lab_definitions', function (Blueprint $table): void {
            $table->dropColumn([
                'lab_id',
                'based_on_revision_id',
                'environment_binding_mode',
                'environment_contract',
                'validation_report',
                'validated_at',
                'published_at',
            ]);
        });
        DB::statement('ALTER TABLE simulation_lab_definitions ALTER COLUMN enterprise_id SET NOT NULL');
        DB::statement('ALTER TABLE simulation_lab_definitions ALTER COLUMN baseline_id SET NOT NULL');
        Schema::dropIfExists('simulation_labs');

        Schema::dropIfExists('simulation_digital_twin_relationships');
        Schema::dropIfExists('simulation_digital_twin_components');

        DB::statement('ALTER TABLE simulation_baselines DROP CONSTRAINT IF EXISTS sim_baseline_revision_owner_fk');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_revision_parent_fk');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_revision_owner_fk');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_revision_lineage_unique');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_revision_enterprise_id_unique');
        DB::statement('ALTER TABLE simulation_digital_twins DROP CONSTRAINT IF EXISTS sim_twin_enterprise_identity_unique');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_lifecycle_check');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_status_check');
        DB::statement("ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_status_check CHECK (status IN ('DRAFT','PUBLISHED'))");
        Schema::table('simulation_digital_twin_revisions', function (Blueprint $table): void {
            $table->dropColumn(['based_on_revision_id', 'validation_report', 'validated_at']);
        });

        Schema::dropIfExists('simulation_device_template_revisions');
        Schema::dropIfExists('simulation_device_templates');
        Schema::dropIfExists('simulation_enterprise_relationships');
        Schema::dropIfExists('simulation_enterprise_entities');
    }
};
