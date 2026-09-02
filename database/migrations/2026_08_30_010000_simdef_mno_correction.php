<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure owner tables have the correct unique constraints for composite FKs
        Schema::table('simulation_enterprise_entities', function (Blueprint $table): void {
            $table->unique(['enterprise_id', 'id'], 'sim_ent_entity_owner_uq');
        });
        
        Schema::table('simulation_enterprise_relationships', function (Blueprint $table): void {
            $table->unique(['enterprise_id', 'id'], 'sim_ent_rel_owner_uq');
        });

        DB::statement('ALTER TABLE simulation_baselines DROP CONSTRAINT IF EXISTS sim_baseline_revision_owner_fk');
        DB::statement('ALTER TABLE simulation_baselines ADD CONSTRAINT sim_baseline_revision_owner_fk FOREIGN KEY (enterprise_id, digital_twin_id, digital_twin_revision_id) REFERENCES simulation_digital_twin_revisions (enterprise_id, digital_twin_id, id) ON DELETE RESTRICT');
        
        // M: Add enterprise_entity_revision_id to components
        Schema::table('simulation_digital_twin_components', function (Blueprint $table): void {
            if (!Schema::hasColumn('simulation_digital_twin_components', 'enterprise_entity_revision_id')) {
                $table->uuid('enterprise_entity_revision_id')->nullable();
            }
        });

        // M: Add entity revisions table
        if (!Schema::hasTable('simulation_enterprise_entity_revisions')) {
            Schema::create('simulation_enterprise_entity_revisions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('enterprise_id');
                $table->uuid('enterprise_entity_id');
                $table->integer('revision');
                $table->string('status', 32)->default('DRAFT');
                $table->uuid('based_on_revision_id')->nullable();
                $table->string('lifecycle_state', 32)->default('ACTIVE');
                $table->jsonb('properties');
                $table->string('digest', 64);
                $table->timestampTz('published_at')->nullable();
                $table->string('created_by', 120)->nullable();
                $table->timestampsTz();
                
                $table->unique(['enterprise_id', 'enterprise_entity_id', 'revision'], 'sim_ent_entity_rev_unique');
                $table->unique(['enterprise_id', 'enterprise_entity_id', 'id'], 'sim_ent_entity_rev_parent_uq');
                $table->foreign(['enterprise_id', 'enterprise_entity_id'], 'sim_ent_entity_rev_owner_fk')
                      ->references(['enterprise_id', 'id'])->on('simulation_enterprise_entities')->restrictOnDelete();
                $table->foreign(['enterprise_id', 'enterprise_entity_id', 'based_on_revision_id'], 'sim_ent_entity_rev_parent_fk')
                      ->references(['enterprise_id', 'enterprise_entity_id', 'id'])->on('simulation_enterprise_entity_revisions')->restrictOnDelete();
            });
            
            // Add FK from components to entity revisions
            DB::statement('ALTER TABLE simulation_digital_twin_components ADD CONSTRAINT sim_twin_comp_ent_rev_fk FOREIGN KEY (enterprise_id, enterprise_entity_id, enterprise_entity_revision_id) REFERENCES simulation_enterprise_entity_revisions (enterprise_id, enterprise_entity_id, id) ON DELETE RESTRICT');
        }

        // M: Backfill Entity Revisions
        $entities = DB::table('simulation_enterprise_entities')->get();
        foreach ($entities as $entity) {
            $hasRevision = DB::table('simulation_enterprise_entity_revisions')
                ->where('enterprise_entity_id', $entity->id)
                ->exists();
                
            if (!$hasRevision) {

$payload = [
                    'enterprise_id' => $entity->enterprise_id,
                    'enterprise_entity_id' => $entity->id,
                    'lifecycle_state' => $entity->lifecycle_state,
                    'properties' => json_decode($entity->properties, true)
                ];
                
                $hashPayload = $payload;
                $sort = function (&$array) use (&$sort) {
                    if (is_array($array)) {
                        ksort($array);
                        foreach ($array as &$value) {
                            if (is_array($value)) {
                                $sort($value);
                            }
                        }
                    }
                };
                $sort($hashPayload);
                $digest = hash('sha256', json_encode($hashPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                $revId = (string) \Illuminate\Support\Str::uuid7();
                DB::table('simulation_enterprise_entity_revisions')->insert([
                    'id' => $revId,
                    'enterprise_id' => $entity->enterprise_id,
                    'enterprise_entity_id' => $entity->id,
                    'revision' => 1,
                    'status' => 'PUBLISHED',
                    'lifecycle_state' => $entity->lifecycle_state,
                    'properties' => $entity->properties,
                    'digest' => $digest,
                    'published_at' => $entity->created_at,
                    'created_by' => $entity->created_by,
                    'created_at' => $entity->created_at,
                    'updated_at' => $entity->updated_at,
                ]);
                
                // Pin existing ENTERPRISE_ENTITY components to this revision
                DB::table('simulation_digital_twin_components')
                    ->where('enterprise_entity_id', $entity->id)
                    ->update(['enterprise_entity_revision_id' => $revId]);
            }
        }

        // M: Add enterprise_relationship_revision_id to Twin relationships
DB::statement('ALTER TABLE simulation_digital_twin_relationships DROP CONSTRAINT IF EXISTS sim_twin_rel_ent_rel_pin_check');
DB::statement('ALTER TABLE simulation_digital_twin_relationships DROP CONSTRAINT IF EXISTS sim_twin_rel_ent_rel_pin_fk');

        Schema::table('simulation_digital_twin_relationships', function (Blueprint $table): void {
            if (!Schema::hasColumn('simulation_digital_twin_relationships', 'enterprise_relationship_id')) {
                $table->uuid('enterprise_relationship_id')->nullable();
                $table->uuid('enterprise_relationship_revision_id')->nullable();
            }
        });

        // M: Add relationship revisions table
        if (!Schema::hasTable('simulation_enterprise_relationship_revisions')) {
            Schema::create('simulation_enterprise_relationship_revisions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('enterprise_id');
                $table->uuid('enterprise_relationship_id');
                $table->integer('revision');
                $table->string('status', 32)->default('DRAFT');
                $table->uuid('based_on_revision_id')->nullable();
                $table->jsonb('properties');
                $table->string('digest', 64);
                $table->timestampTz('published_at')->nullable();
                $table->string('created_by', 120)->nullable();
                $table->timestampsTz();
                
                $table->unique(['enterprise_id', 'enterprise_relationship_id', 'revision'], 'sim_ent_rel_rev_unique');
                $table->unique(['enterprise_id', 'enterprise_relationship_id', 'id'], 'sim_ent_rel_rev_parent_uq');
                $table->foreign(['enterprise_id', 'enterprise_relationship_id'], 'sim_ent_rel_rev_owner_fk')
                      ->references(['enterprise_id', 'id'])->on('simulation_enterprise_relationships')->restrictOnDelete();
                $table->foreign(['enterprise_id', 'enterprise_relationship_id', 'based_on_revision_id'], 'sim_ent_rel_rev_parent_fk')
                      ->references(['enterprise_id', 'enterprise_relationship_id', 'id'])->on('simulation_enterprise_relationship_revisions')->restrictOnDelete();
            });
        }
        
        // M: Backfill Relationship Revisions
        $relationships = DB::table('simulation_enterprise_relationships')->get();
        foreach ($relationships as $rel) {
            $hasRevision = DB::table('simulation_enterprise_relationship_revisions')
                ->where('enterprise_relationship_id', $rel->id)
                ->exists();
                
            if (!$hasRevision) {

$payload = [
                    'enterprise_id' => $rel->enterprise_id,
                    'enterprise_relationship_id' => $rel->id,
                    'properties' => json_decode($rel->properties, true)
                ];
                $hashPayload = $payload;
                $sort = function (&$array) use (&$sort) {
                    if (is_array($array)) {
                        ksort($array);
                        foreach ($array as &$value) {
                            if (is_array($value)) {
                                $sort($value);
                            }
                        }
                    }
                };
                $sort($hashPayload);
                $digest = hash('sha256', json_encode($hashPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                $revId = (string) \Illuminate\Support\Str::uuid7();
                DB::table('simulation_enterprise_relationship_revisions')->insert([
                    'id' => $revId,
                    'enterprise_id' => $rel->enterprise_id,
                    'enterprise_relationship_id' => $rel->id,
                    'revision' => 1,
                    'status' => 'PUBLISHED',
                    'properties' => $rel->properties,
                    'digest' => $digest,
                    'published_at' => $rel->created_at,
                    'created_by' => $rel->created_by,
                    'created_at' => $rel->created_at,
                    'updated_at' => $rel->updated_at,
                ]);
            }
        }

                Schema::table('simulation_enterprise_entities', function (Blueprint $table): void {
            $table->dropColumn(['lifecycle_state', 'properties']);
        });

        Schema::table('simulation_enterprise_relationships', function (Blueprint $table): void {
            $table->dropColumn(['properties']);
        });

        Schema::table('simulation_lab_definitions', function (Blueprint $table): void {
            if (!Schema::hasColumn('simulation_lab_definitions', 'knowledge_links')) {
                $table->jsonb('knowledge_links')->nullable();
                $table->jsonb('simulation_capabilities')->nullable();
                $table->jsonb('environment_profile')->nullable();
                $table->jsonb('initial_state')->nullable();
                $table->jsonb('preconditions')->nullable();
                $table->jsonb('roles')->nullable();
                $table->jsonb('tools')->nullable();
                $table->jsonb('expected_signals')->nullable();
                $table->jsonb('validation_rules')->nullable();
                $table->jsonb('safety_reset')->nullable();
                $table->jsonb('result_schema')->nullable();
                $table->jsonb('completion_criteria')->nullable();
            }
            if (!Schema::hasColumn('simulation_lab_definitions', 'schema_version')) {
                $table->string('schema_version', 16)->default('LEGACY_V1');
            }
        });
        
// DB-level constraints for Twin Relationship pins
        DB::statement('ALTER TABLE simulation_digital_twin_relationships ADD CONSTRAINT sim_twin_rel_ent_rel_pin_check CHECK (
            (enterprise_relationship_id IS NULL AND enterprise_relationship_revision_id IS NULL) OR 
            (enterprise_relationship_id IS NOT NULL AND enterprise_relationship_revision_id IS NOT NULL)
        )');

        // Ensure same-lineage composite parent FKs and owner composite FKs
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_revision_parent_fk');
        DB::statement('ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_revision_parent_fk FOREIGN KEY (enterprise_id, digital_twin_id, based_on_revision_id) REFERENCES simulation_digital_twin_revisions (enterprise_id, digital_twin_id, id) ON DELETE RESTRICT');
        
        DB::statement('ALTER TABLE simulation_digital_twin_relationships DROP CONSTRAINT IF EXISTS sim_twin_rel_ent_rel_pin_fk');
        DB::statement('ALTER TABLE simulation_digital_twin_relationships ADD CONSTRAINT sim_twin_rel_ent_rel_pin_fk FOREIGN KEY (enterprise_id, enterprise_relationship_id, enterprise_relationship_revision_id) REFERENCES simulation_enterprise_relationship_revisions (enterprise_id, enterprise_relationship_id, id) ON DELETE RESTRICT');

    }

    public function down(): void
    {
        $hasAnyR20Lab = DB::table('simulation_lab_definitions')
            ->where('status', 'PUBLISHED')
            ->where('schema_version', 'R20')
            ->exists();
            
        $hasEntityRevisions = DB::table('simulation_enterprise_entity_revisions')
            ->where('revision', '>', 1)
            ->exists();
            
        $hasRelationshipRevisions = DB::table('simulation_enterprise_relationship_revisions')
            ->where('revision', '>', 1)
            ->exists();
            
        $hasAnyR20Lab = DB::table('simulation_lab_definitions')
            ->where('schema_version', 'R20')
            ->exists(); // ANY R20 facet data must refuse rollback

        if ($hasAnyR20Lab || $hasEntityRevisions || $hasRelationshipRevisions) {
            throw new RuntimeException('Cannot rollback: R20 authored published labs or new entity revisions exist. Rollback requires manual removal or down-migration approval.');
        }

        Schema::table('simulation_lab_definitions', function (Blueprint $table): void {
            if (Schema::hasColumn('simulation_lab_definitions', 'knowledge_links')) {
                $table->dropColumn([
                    'knowledge_links',
                    'simulation_capabilities',
                    'environment_profile',
                    'initial_state',
                    'preconditions',
                    'roles',
                    'tools',
                    'expected_signals',
                    'validation_rules',
                    'safety_reset',
                    'result_schema',
                    'completion_criteria',
                    'schema_version'
                ]);
            }
        });
        
        DB::statement('ALTER TABLE simulation_digital_twin_components DROP CONSTRAINT IF EXISTS sim_twin_comp_ent_rev_fk');
        Schema::table('simulation_digital_twin_components', function (Blueprint $table): void {
            if (Schema::hasColumn('simulation_digital_twin_components', 'enterprise_entity_revision_id')) {
                $table->dropColumn('enterprise_entity_revision_id');
            }
        });
        
DB::statement('ALTER TABLE simulation_digital_twin_relationships DROP CONSTRAINT IF EXISTS sim_twin_rel_ent_rel_pin_check');
DB::statement('ALTER TABLE simulation_digital_twin_relationships DROP CONSTRAINT IF EXISTS sim_twin_rel_ent_rel_pin_fk');

DB::statement('ALTER TABLE simulation_enterprise_relationship_revisions DROP CONSTRAINT IF EXISTS sim_ent_rel_rev_lineage_uq');
        Schema::table('simulation_digital_twin_relationships', function (Blueprint $table): void {
            if (Schema::hasColumn('simulation_digital_twin_relationships', 'enterprise_id')) {
                $table->dropColumn('enterprise_id');
            }
        });
DB::statement('ALTER TABLE simulation_enterprise_relationship_revisions DROP CONSTRAINT IF EXISTS sim_ent_rel_rev_lineage_uq');
        Schema::table('simulation_digital_twin_relationships', function (Blueprint $table): void {
            if (Schema::hasColumn('simulation_digital_twin_relationships', 'enterprise_id')) {
                $table->dropColumn('enterprise_id');
            }
        });
        Schema::table('simulation_digital_twin_relationships', function (Blueprint $table): void {
            if (Schema::hasColumn('simulation_digital_twin_relationships', 'enterprise_relationship_revision_id')) {
                $table->dropColumn(['enterprise_relationship_id', 'enterprise_relationship_revision_id']);
            }
        });
        
                Schema::table('simulation_enterprise_entities', function (Blueprint $table): void {
            $table->string('lifecycle_state', 32)->default('ACTIVE');
            $table->jsonb('properties')->default('{}');
        });

        Schema::table('simulation_enterprise_relationships', function (Blueprint $table): void {
            $table->jsonb('properties')->default('{}');
        });
        
        // Restore legacy data from revision 1
        $entRevs = DB::table('simulation_enterprise_entity_revisions')->where('revision', 1)->get();
        foreach ($entRevs as $rev) {
            DB::table('simulation_enterprise_entities')
                ->where('id', $rev->enterprise_entity_id)
                ->update([
                    'lifecycle_state' => $rev->lifecycle_state,
                    'properties' => $rev->properties
                ]);
        }
        
        $relRevs = DB::table('simulation_enterprise_relationship_revisions')->where('revision', 1)->get();
        foreach ($relRevs as $rev) {
            DB::table('simulation_enterprise_relationships')
                ->where('id', $rev->enterprise_relationship_id)
                ->update([
                    'properties' => $rev->properties
                ]);
        }

        Schema::dropIfExists('simulation_enterprise_entity_revisions');
        Schema::dropIfExists('simulation_enterprise_relationship_revisions');

        Schema::table('simulation_enterprise_entities', function (Blueprint $table): void {
            $table->dropUnique('sim_ent_entity_owner_uq');
        });
        Schema::table('simulation_enterprise_relationships', function (Blueprint $table): void {
            $table->dropUnique('sim_ent_rel_owner_uq');
        });

        
        DB::statement('ALTER TABLE simulation_baselines DROP CONSTRAINT IF EXISTS sim_baseline_revision_owner_fk');
        
        DB::statement('ALTER TABLE simulation_digital_twin_revisions DROP CONSTRAINT IF EXISTS sim_twin_revision_parent_fk');
        // Do not silently weaken same-lineage ownership by restoring an unsafe bare FK.
        DB::statement('ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_revision_parent_fk FOREIGN KEY (enterprise_id, digital_twin_id, based_on_revision_id) REFERENCES simulation_digital_twin_revisions (enterprise_id, digital_twin_id, id) ON DELETE RESTRICT');
    }
};
