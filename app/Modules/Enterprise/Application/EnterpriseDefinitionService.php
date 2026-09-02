<?php

namespace App\Modules\Enterprise\Application;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;

final class EnterpriseDefinitionService implements EnterpriseDefinitionAuthoring
{
    private const ENTITIES = 'simulation_enterprise_entities';

    private const ENTERPRISE_RELATIONSHIPS = 'simulation_enterprise_relationships';

    private const DEVICE_TEMPLATES = 'simulation_device_templates';

    private const DIGITAL_TWINS = 'simulation_digital_twins';

    private const TWIN_REVISIONS = 'simulation_digital_twin_revisions';

    private const TWIN_COMPONENTS = 'simulation_digital_twin_components';

    private const TWIN_RELATIONSHIPS = 'simulation_digital_twin_relationships';

    private const DEVICE_REVISIONS = 'simulation_device_template_revisions';

    /** @var list<string> */
    private const RELATIONSHIP_TYPES = [
        'HOSTS',
        'DEPENDS_ON',
        'AUTHENTICATES_WITH',
        'CONNECTS_TO',
        'MANAGED_BY',
        'PROTECTED_BY',
        'STORES',
        'ROUTES_TO',
        'MEMBER_OF',
    ];

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
public function createEntity(string $enterpriseId, array $attributes, string $actorId): array
    {
        $this->assertActor($actorId);
        $this->requireRow('simulation_enterprises', $enterpriseId);
        $id = (string) Str::uuid7();
        $now = now();
        $revId = (string) Str::uuid7();

        return DB::transaction(function () use ($enterpriseId, $attributes, $actorId, $id, $revId, $now) {
            DB::table(self::ENTITIES)->insert([
                'id' => $id,
                'enterprise_id' => $enterpriseId,
                'entity_key' => $this->requiredString($attributes, 'entity_key'),
                'entity_type' => $this->requiredString($attributes, 'entity_type'),
                'name_ar' => $this->requiredString($attributes, 'name_ar'),
                'name_en' => $this->nullableString($attributes['name_en'] ?? null),
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            $payload = [
                'enterprise_id' => $enterpriseId,
                'enterprise_entity_id' => $id,
                'lifecycle_state' => $this->nullableString($attributes['lifecycle_state'] ?? null) ?? 'ACTIVE',
                'properties' => $this->arrayValue($attributes['properties'] ?? [])
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

            DB::table('simulation_enterprise_entity_revisions')->insert([
                'id' => $revId,
                'enterprise_id' => $enterpriseId,
                'enterprise_entity_id' => $id,
                'revision' => 1,
                'status' => 'PUBLISHED',
                'based_on_revision_id' => null,
                'lifecycle_state' => $payload['lifecycle_state'],
                'properties' => $this->json($payload['properties']),
                'digest' => $digest,
                'published_at' => $now,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row(self::ENTITIES, $id);
        });
    }
    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
public function createRelationship(string $enterpriseId, array $attributes, string $actorId): array
    {
        $this->assertActor($actorId);
        $sourceId = $this->requiredString($attributes, 'source_entity_id');
        $targetId = $this->requiredString($attributes, 'target_entity_id');
        $relationshipType = $this->requiredString($attributes, 'relationship_type');

        if (! in_array($relationshipType, self::RELATIONSHIP_TYPES, true)) {
            throw new DomainException('Unsupported Enterprise relationship type.');
        }
        $this->requireOwnedEntity($enterpriseId, $sourceId);
        $this->requireOwnedEntity($enterpriseId, $targetId);

        $id = (string) Str::uuid7();
        $now = now();
        $revId = (string) Str::uuid7();
        
        return DB::transaction(function () use ($enterpriseId, $sourceId, $targetId, $relationshipType, $attributes, $actorId, $id, $revId, $now) {
            DB::table(self::ENTERPRISE_RELATIONSHIPS)->insert([
                'id' => $id,
                'enterprise_id' => $enterpriseId,
                'source_entity_id' => $sourceId,
                'target_entity_id' => $targetId,
                'relationship_type' => $relationshipType,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            $payload = [
                'enterprise_id' => $enterpriseId,
                'enterprise_relationship_id' => $id,
                'properties' => $this->arrayValue($attributes['properties'] ?? [])
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

            DB::table('simulation_enterprise_relationship_revisions')->insert([
                'id' => $revId,
                'enterprise_id' => $enterpriseId,
                'enterprise_relationship_id' => $id,
                'revision' => 1,
                'status' => 'PUBLISHED',
                'based_on_revision_id' => null,
                'properties' => $this->json($payload['properties']),
                'digest' => $digest,
                'published_at' => $now,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row(self::ENTERPRISE_RELATIONSHIPS, $id);
        });
    }
    /** @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
public function createEntityDraft(string $enterpriseId, string $entityId, array $attributes, string $actorId): array
    {
        $this->assertActor($actorId);
        $this->requireOwnedEntity($enterpriseId, $entityId);

        return DB::transaction(function () use ($enterpriseId, $entityId, $attributes, $actorId): array {
            DB::table(self::ENTITIES)->where('id', $entityId)->lockForUpdate()->first();
            
            $hasDraft = DB::table('simulation_enterprise_entity_revisions')
                ->where('enterprise_entity_id', $entityId)
                ->where('status', 'DRAFT')
                ->exists();
            if ($hasDraft) {
                throw new LogicException('Entity already has an open revision.');
            }

            $previous = DB::table('simulation_enterprise_entity_revisions')
                ->where('enterprise_entity_id', $entityId)
                ->where('status', 'PUBLISHED')
                ->orderByDesc('revision')
                ->first();
                
            $revision = (int) DB::table('simulation_enterprise_entity_revisions')
                ->where('enterprise_entity_id', $entityId)
                ->max('revision') + 1;

            $revId = (string) Str::uuid7();
            $now = now();
            
            $payload = [
                'enterprise_id' => $enterpriseId,
                'enterprise_entity_id' => $entityId,
                'lifecycle_state' => $this->nullableString($attributes['lifecycle_state'] ?? null) ?? ($previous->lifecycle_state ?? 'ACTIVE'),
                'properties' => $this->arrayValue($attributes['properties'] ?? [])
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

            DB::table('simulation_enterprise_entity_revisions')->insert([
                'id' => $revId,
                'enterprise_id' => $enterpriseId,
                'enterprise_entity_id' => $entityId,
                'revision' => $revision,
                'status' => 'DRAFT',
                'based_on_revision_id' => $previous?->id,
                'lifecycle_state' => $payload['lifecycle_state'],
                'properties' => $this->json($payload['properties']),
                'digest' => $digest,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row('simulation_enterprise_entity_revisions', $revId);
        });
    }

    public function publishEntityRevision(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($revisionId, $actorId): array {
            $revision = DB::table('simulation_enterprise_entity_revisions')->where('id', $revisionId)->where('status', 'DRAFT')->first();
            if ($revision === null) {
                throw new LogicException('Only a DRAFT entity revision can be published.');
            }
            DB::table(self::ENTITIES)->where('id', $revision->enterprise_entity_id)->lockForUpdate()->first();

            DB::table('simulation_enterprise_entity_revisions')->where('id', $revisionId)->update([
                'status' => 'PUBLISHED',
                'published_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->row('simulation_enterprise_entity_revisions', $revisionId);
        });
    }

    public function createRelationshipDraft(string $enterpriseId, string $relationshipId, array $attributes, string $actorId): array
    {
        $this->assertActor($actorId);
        $rel = DB::table(self::ENTERPRISE_RELATIONSHIPS)->where('id', $relationshipId)->where('enterprise_id', $enterpriseId)->first();
        if ($rel === null) {
            throw new DomainException('Relationship not found.');
        }

        return DB::transaction(function () use ($enterpriseId, $relationshipId, $attributes, $actorId): array {
            DB::table(self::ENTERPRISE_RELATIONSHIPS)->where('id', $relationshipId)->lockForUpdate()->first();
            
            $hasDraft = DB::table('simulation_enterprise_relationship_revisions')
                ->where('enterprise_relationship_id', $relationshipId)
                ->where('status', 'DRAFT')
                ->exists();
            if ($hasDraft) {
                throw new LogicException('Relationship already has an open revision.');
            }

            $previous = DB::table('simulation_enterprise_relationship_revisions')
                ->where('enterprise_relationship_id', $relationshipId)
                ->where('status', 'PUBLISHED')
                ->orderByDesc('revision')
                ->first();
                
            $revision = (int) DB::table('simulation_enterprise_relationship_revisions')
                ->where('enterprise_relationship_id', $relationshipId)
                ->max('revision') + 1;

            $revId = (string) Str::uuid7();
            $now = now();
            
            $payload = [
                'enterprise_id' => $enterpriseId,
                'enterprise_relationship_id' => $relationshipId,
                'properties' => $this->arrayValue($attributes['properties'] ?? [])
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

            DB::table('simulation_enterprise_relationship_revisions')->insert([
                'id' => $revId,
                'enterprise_id' => $enterpriseId,
                'enterprise_relationship_id' => $relationshipId,
                'revision' => $revision,
                'status' => 'DRAFT',
                'based_on_revision_id' => $previous?->id,
                'properties' => $this->json($payload['properties']),
                'digest' => $digest,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row('simulation_enterprise_relationship_revisions', $revId);
        });
    }

    public function publishRelationshipRevision(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($revisionId, $actorId): array {
            $revision = DB::table('simulation_enterprise_relationship_revisions')->where('id', $revisionId)->where('status', 'DRAFT')->first();
            if ($revision === null) {
                throw new LogicException('Only a DRAFT relationship revision can be published.');
            }
            DB::table(self::ENTERPRISE_RELATIONSHIPS)->where('id', $revision->enterprise_relationship_id)->lockForUpdate()->first();

            DB::table('simulation_enterprise_relationship_revisions')->where('id', $revisionId)->update([
                'status' => 'PUBLISHED',
                'published_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->row('simulation_enterprise_relationship_revisions', $revisionId);
        });
    }

    public function createDeviceTemplateDraft(
        string $enterpriseId,
        string $templateKey,
        string $deviceType,
        string $nameAr,
        array $definition,
        string $actorId,
    ): array {
        $this->assertActor($actorId);
        $this->requireRow('simulation_enterprises', $enterpriseId);

        return DB::transaction(function () use ($enterpriseId, $templateKey, $deviceType, $nameAr, $definition, $actorId): array {
            $template = DB::table(self::DEVICE_TEMPLATES)
                ->where('enterprise_id', $enterpriseId)
                ->where('template_key', $templateKey)
                ->lockForUpdate()
                ->first();
            $now = now();

            if ($template === null) {
                $templateId = (string) Str::uuid7();
                DB::table(self::DEVICE_TEMPLATES)->insert([
                    'id' => $templateId,
                    'enterprise_id' => $enterpriseId,
                    'template_key' => $templateKey,
                    'device_type' => $deviceType,
                    'name_ar' => $nameAr,
                    'name_en' => null,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $templateId = (string) $template->id;
                $openRevision = DB::table(self::DEVICE_REVISIONS)
                    ->where('device_template_id', $templateId)
                    ->whereIn('status', ['DRAFT', 'VALIDATED'])
                    ->exists();
                if ($openRevision) {
                    throw new LogicException('Device Template already has an open revision.');
                }
            }

            $previous = DB::table(self::DEVICE_REVISIONS)
                ->where('device_template_id', $templateId)
                ->where('status', 'PUBLISHED')
                ->orderByDesc('revision')
                ->first();
            $revision = (int) DB::table(self::DEVICE_REVISIONS)
                ->where('device_template_id', $templateId)
                ->max('revision') + 1;
            $revisionId = (string) Str::uuid7();
            $payload = $this->deviceDefinition($definition);

            DB::table(self::DEVICE_REVISIONS)->insert([
                'id' => $revisionId,
                'enterprise_id' => $enterpriseId,
                'device_template_id' => $templateId,
                'based_on_revision_id' => $previous?->id,
                'revision' => $revision,
                'status' => 'DRAFT',
                ...$this->jsonColumns($payload),
                'validation_report' => null,
                'digest' => $this->digest($payload),
                'validated_at' => null,
                'published_at' => null,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row(self::DEVICE_REVISIONS, $revisionId);
        });
    }

    /** @return array<string, mixed> */
    public function validateDeviceTemplateRevision(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);
        $revision = $this->requireDefinitionRevision(self::DEVICE_REVISIONS, $revisionId, ['DRAFT', 'VALIDATED']);
        $payload = $this->deviceRevisionPayload($revision);
        $errors = [];

        if ($this->stringList($payload['capabilities']) === []) {
            $errors[] = 'Device Template requires at least one declared simulation capability.';
        }
        if ($this->arrayValue($payload['state_model']) === []) {
            $errors[] = 'Device Template requires a typed state model.';
        }
        if ($this->arrayValue($payload['behavior_rules']) === []) {
            $errors[] = 'Device Template requires behavior rules.';
        }

        $report = $this->validationReport($errors, $this->digest($payload), $actorId);
        DB::table(self::DEVICE_REVISIONS)->where('id', $revisionId)->update([
            'status' => $errors === [] ? 'VALIDATED' : 'DRAFT',
            'validation_report' => $this->json($report),
            'validated_at' => $errors === [] ? now() : null,
            'digest' => $report['validated_digest'],
            'updated_at' => now(),
        ]);

        return $this->row(self::DEVICE_REVISIONS, $revisionId);
    }

    /** @return array<string, mixed> */
    public function publishDeviceTemplateRevision(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($revisionId): array {
            $revision = DB::table(self::DEVICE_REVISIONS)->where('id', $revisionId)->lockForUpdate()->first();
            if ($revision === null || (string) $revision->status !== 'VALIDATED') {
                throw new LogicException('Device Template publication requires a validated draft.');
            }
            $this->assertValidatedDigest($revision, $this->digest($this->deviceRevisionPayload($revision)));
            DB::table(self::DEVICE_REVISIONS)->where('id', $revisionId)->update([
                'status' => 'PUBLISHED',
                'published_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->row(self::DEVICE_REVISIONS, $revisionId);
        });
    }

    /** @param array<string, mixed> $behaviorModel
     * @return array<string, mixed>
     */
    public function createDigitalTwinDraft(
        string $enterpriseId,
        string $slug,
        string $nameAr,
        array $behaviorModel,
        string $actorId,
    ): array {
        $this->assertActor($actorId);
        $this->requireRow('simulation_enterprises', $enterpriseId);

        return DB::transaction(function () use ($enterpriseId, $slug, $nameAr, $behaviorModel, $actorId): array {
            $twin = DB::table(self::DIGITAL_TWINS)
                ->where('enterprise_id', $enterpriseId)
                ->where('slug', $slug)
                ->lockForUpdate()
                ->first();
            $now = now();

            if ($twin === null) {
                $twinId = (string) Str::uuid7();
                DB::table(self::DIGITAL_TWINS)->insert([
                    'id' => $twinId,
                    'enterprise_id' => $enterpriseId,
                    'slug' => $slug,
                    'name_ar' => $nameAr,
                    'name_en' => null,
                    'provenance' => 'SIMULATED',
                    'is_fixture' => false,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $twinId = (string) $twin->id;
                if (DB::table(self::TWIN_REVISIONS)->where('digital_twin_id', $twinId)->whereIn('status', ['DRAFT', 'VALIDATED'])->exists()) {
                    throw new LogicException('Digital Twin already has an open revision.');
                }
            }

            $previous = DB::table(self::TWIN_REVISIONS)
                ->where('digital_twin_id', $twinId)
                ->where('status', 'PUBLISHED')
                ->orderByDesc('revision')
                ->first();
            $revision = (int) DB::table(self::TWIN_REVISIONS)->where('digital_twin_id', $twinId)->max('revision') + 1;
            $revisionId = (string) Str::uuid7();
            $topology = ['nodes' => [], 'links' => []];
            $digest = $this->digest(['topology' => $topology, 'behavior_model' => $behaviorModel]);

            DB::table(self::TWIN_REVISIONS)->insert([
                'id' => $revisionId,
                'enterprise_id' => $enterpriseId,
                'digital_twin_id' => $twinId,
                'based_on_revision_id' => $previous?->id,
                'revision' => $revision,
                'status' => 'DRAFT',
                'topology' => $this->json($topology),
                'behavior_model' => $this->json($behaviorModel),
                'validation_report' => null,
                'digest' => $digest,
                'validated_at' => null,
                'published_at' => null,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row(self::TWIN_REVISIONS, $revisionId);
        });
    }

    /** @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function addDigitalTwinComponent(string $revisionId, array $component, string $actorId): array
    {
        $this->assertActor($actorId);
        $revision = $this->requireDefinitionRevision(self::TWIN_REVISIONS, $revisionId, ['DRAFT']);
        $scope = $this->requiredString($component, 'ownership_scope');
        $entityId = $this->nullableString($component['enterprise_entity_id'] ?? null);
        $entityRevisionId = $this->nullableString($component['enterprise_entity_revision_id'] ?? null);
        $templateRevisionId = $this->nullableString($component['device_template_revision_id'] ?? null);

        if ($scope === 'ENTERPRISE_ENTITY') {
            if ($entityId === null || $entityRevisionId === null) {
                throw new DomainException('Enterprise-backed Twin components require both Enterprise Entity ID and Revision ID.');
            }
            $entity = $this->requireOwnedEntity((string) $revision->enterprise_id, $entityId);
            $entityRev = DB::table('simulation_enterprise_entity_revisions')
                ->where('id', $entityRevisionId)
                ->where('enterprise_entity_id', $entityId)
                ->where('enterprise_id', $revision->enterprise_id)
                ->where('status', 'PUBLISHED')
                ->first();
            if ($entityRev === null) {
                throw new DomainException('Invalid or non-PUBLISHED Enterprise Entity Revision pinned.');
            }
        } elseif ($scope === 'SIMULATION_LOCAL') {
            if ($entityId !== null || $entityRevisionId !== null) {
                throw new DomainException('Simulation-local Twin components cannot claim Enterprise Entity ownership.');
            }
        } else {
            throw new DomainException('Digital Twin component ownership scope is invalid.');
        }

        $id = (string) Str::uuid7();
        $now = now();

        DB::table(self::TWIN_COMPONENTS)->insert([
            'id' => $id,
            'enterprise_id' => $revision->enterprise_id,
            'digital_twin_revision_id' => $revisionId,
            'component_key' => $this->requiredString($component, 'component_key'),
            'ownership_scope' => $scope,
            'enterprise_entity_id' => $entityId,
            'enterprise_entity_revision_id' => $entityRevisionId,
            'device_template_revision_id' => $templateRevisionId,
            'name_ar' => $this->requiredString($component, 'name_ar'),
            'simulation_definition' => $this->json($this->arrayValue($component['simulation_definition'] ?? [])),
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->refreshTwinDraftDigest($revisionId);

        return $this->row(self::TWIN_COMPONENTS, $id);
    }

    /** @param array<string, mixed> $relationship
     * @return array<string, mixed>
     */
    public function addDigitalTwinRelationship(string $revisionId, array $relationship, string $actorId): array
    {
        $this->assertActor($actorId);

        $revision = $this->requireMutableTwinRevision($revisionId);

        $sourceId = $this->requiredString($relationship, 'source_component_id');
        $targetId = $this->requiredString($relationship, 'target_component_id');

$relationshipType = $this->requiredString($relationship, 'relationship_type');
        if (! in_array($relationshipType, self::RELATIONSHIP_TYPES, true)) {
            throw new DomainException('Unsupported Digital Twin relationship type.');
        }
        $source = $this->requireTwinComponent($revisionId, $sourceId);
        $target = $this->requireTwinComponent($revisionId, $targetId);
        
        $entRelId = $this->nullableString($relationship['enterprise_relationship_id'] ?? null);
        $entRelRevId = $this->nullableString($relationship['enterprise_relationship_revision_id'] ?? null);
        
        if (($entRelId === null) !== ($entRelRevId === null)) {
            throw new DomainException('Relationship bindings must have both ID and Revision ID null, or both non-null.');
        }
        
        if ($entRelId !== null) {
            $entRelRev = DB::table('simulation_enterprise_relationship_revisions')
                ->where('id', $entRelRevId)
                ->where('enterprise_relationship_id', $entRelId)
                ->where('enterprise_id', $revision->enterprise_id)
                ->where('status', 'PUBLISHED')
                ->first();
                
            if ($entRelRev === null) {
                throw new DomainException('Invalid or non-PUBLISHED Enterprise Relationship Revision pinned.');
            }
            
            $entRel = DB::table('simulation_enterprise_relationships')
                ->where('id', $entRelId)
                ->first();
                
            if ($entRel->source_entity_id !== $source->enterprise_entity_id || $entRel->target_entity_id !== $target->enterprise_entity_id) {
                throw new DomainException('Pinned Relationship endpoints do not match the Twin component endpoints.');
            }
        }

        $id = (string) Str::uuid7();
        $now = now();

DB::table(self::TWIN_RELATIONSHIPS)->insert([
            'id' => $id,
            'enterprise_id' => $revision->enterprise_id,
            'digital_twin_revision_id' => $revisionId,

            'source_component_id' => $sourceId,
            'target_component_id' => $targetId,
            'enterprise_relationship_id' => $entRelId,
            'enterprise_relationship_revision_id' => $entRelRevId,
            'relationship_type' => $this->requiredString($relationship, 'relationship_type'),
            'properties' => $this->json($this->arrayValue($relationship['properties'] ?? [])),
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->refreshTwinDraftDigest($revisionId);


        return $this->row(self::TWIN_RELATIONSHIPS, $id);
    }

        

    /** @return array<string, mixed> */
    public function validateDigitalTwinRevision(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);
        $revision = $this->requireDefinitionRevision(self::TWIN_REVISIONS, $revisionId, ['DRAFT', 'VALIDATED']);
        $payload = $this->twinRevisionPayload($revision);
        $components = $this->arrayValue($payload['components']);
        $errors = [];
        $warnings = [];

        if ($components === []) {
            $errors[] = 'Digital Twin revision requires at least one explicit component.';
        }
        if ($this->arrayValue($payload['behavior_model']) === []) {
            $errors[] = 'Digital Twin revision requires a behavior model.';
        }
        if ($components !== [] && collect($components)->every(fn (mixed $component): bool => is_array($component) && ($component['ownership_scope'] ?? null) === 'SIMULATION_LOCAL')) {
            $warnings[] = 'Digital Twin contains only explicitly simulation-local components.';
        }

        $digest = $this->digest($payload);
        $report = $this->validationReport($errors, $digest, $actorId, $warnings);
        DB::table(self::TWIN_REVISIONS)->where('id', $revisionId)->update([
            'status' => $errors === [] ? 'VALIDATED' : 'DRAFT',
            'topology' => $this->json($this->arrayValue($payload['topology'])),
            'validation_report' => $this->json($report),
            'validated_at' => $errors === [] ? now() : null,
            'digest' => $digest,
            'updated_at' => now(),
        ]);

        return $this->row(self::TWIN_REVISIONS, $revisionId);
    }

    /** @return array<string, mixed> */
    public function publishDigitalTwinRevision(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($revisionId): array {
            $revision = DB::table(self::TWIN_REVISIONS)->where('id', $revisionId)->lockForUpdate()->first();
            if ($revision === null || (string) $revision->status !== 'VALIDATED') {
                throw new LogicException('Digital Twin publication requires a validated draft.');
            }
            $payload = $this->twinRevisionPayload($revision);
            $digest = $this->digest($payload);
            $this->assertValidatedDigest($revision, $digest);
            DB::table(self::TWIN_REVISIONS)->where('id', $revisionId)->update([
                'status' => 'PUBLISHED',
                'topology' => $this->json($this->arrayValue($payload['topology'])),
                'digest' => $digest,
                'published_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->row(self::TWIN_REVISIONS, $revisionId);
        });
    }

    /** @return array<string, mixed> */
    public function cloneDigitalTwinRevision(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($revisionId, $actorId): array {
            $source = DB::table(self::TWIN_REVISIONS)->where('id', $revisionId)->where('status', 'PUBLISHED')->lockForUpdate()->first();
            if ($source === null) {
                throw new LogicException('Only a published Digital Twin revision can seed a new draft.');
            }
            if (DB::table(self::TWIN_REVISIONS)->where('digital_twin_id', $source->digital_twin_id)->whereIn('status', ['DRAFT', 'VALIDATED'])->exists()) {
                throw new LogicException('Digital Twin already has an open revision.');
            }

            $newRevisionId = (string) Str::uuid7();
            $now = now();
            $nextRevision = (int) DB::table(self::TWIN_REVISIONS)->where('digital_twin_id', $source->digital_twin_id)->max('revision') + 1;
            DB::table(self::TWIN_REVISIONS)->insert([
                'id' => $newRevisionId,
                'enterprise_id' => $source->enterprise_id,
                'digital_twin_id' => $source->digital_twin_id,
                'based_on_revision_id' => $source->id,
                'revision' => $nextRevision,
                'status' => 'DRAFT',
                'topology' => $source->topology,
                'behavior_model' => $source->behavior_model,
                'validation_report' => null,
                'digest' => $source->digest,
                'validated_at' => null,
                'published_at' => null,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $componentMap = [];
            foreach (DB::table(self::TWIN_COMPONENTS)->where('digital_twin_revision_id', $source->id)->get() as $component) {
                $newComponentId = (string) Str::uuid7();
                $componentMap[(string) $component->id] = $newComponentId;
                DB::table(self::TWIN_COMPONENTS)->insert([
                    'id' => $newComponentId,
                    'enterprise_id' => $component->enterprise_id,
                    'digital_twin_revision_id' => $newRevisionId,
                    'component_key' => $component->component_key,
                    'ownership_scope' => $component->ownership_scope,
                    'enterprise_entity_id' => $component->enterprise_entity_id,
                    'enterprise_entity_revision_id' => $component->enterprise_entity_revision_id ?? null,
                    'device_template_revision_id' => $component->device_template_revision_id,
                    'name_ar' => $component->name_ar,
                    'simulation_definition' => $component->simulation_definition,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            foreach (DB::table(self::TWIN_RELATIONSHIPS)->where('digital_twin_revision_id', $source->id)->get() as $relationship) {
                DB::table(self::TWIN_RELATIONSHIPS)->insert([
                    'id' => (string) Str::uuid7(),
                    'digital_twin_revision_id' => $newRevisionId,
                    'source_component_id' => $componentMap[(string) $relationship->source_component_id],
                    'target_component_id' => $componentMap[(string) $relationship->target_component_id],
                    'relationship_type' => $relationship->relationship_type,
                    'properties' => $relationship->properties,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $this->refreshTwinDraftDigest($newRevisionId);

            return $this->row(self::TWIN_REVISIONS, $newRevisionId);
        });
    }

    private function refreshTwinDraftDigest(string $revisionId): void
    {
        $revision = $this->requireDefinitionRevision(self::TWIN_REVISIONS, $revisionId, ['DRAFT']);
        $payload = $this->twinRevisionPayload($revision);
        DB::table(self::TWIN_REVISIONS)->where('id', $revisionId)->update([
            'topology' => $this->json($this->arrayValue($payload['topology'])),
            'digest' => $this->digest($payload),
            'validation_report' => null,
            'validated_at' => null,
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function twinRevisionPayload(stdClass $revision): array
    {
        $components = DB::table(self::TWIN_COMPONENTS)
            ->where('digital_twin_revision_id', $revision->id)
            ->orderBy('component_key')
            ->get()
            ->map(fn (stdClass $component): array => [
                'id' => (string) $component->id,
                'component_key' => (string) $component->component_key,
                'ownership_scope' => (string) $component->ownership_scope,
                'enterprise_entity_id' => $component->enterprise_entity_id === null ? null : (string) $component->enterprise_entity_id,
                'enterprise_entity_revision_id' => ($component->enterprise_entity_revision_id ?? null) === null ? null : (string) $component->enterprise_entity_revision_id,
                'device_template_revision_id' => $component->device_template_revision_id === null ? null : (string) $component->device_template_revision_id,
                'name_ar' => (string) $component->name_ar,
                'simulation_definition' => $this->decode($component->simulation_definition),
            ])->all();
        $componentKeys = collect($components)->mapWithKeys(fn (array $component): array => [$component['id'] => $component['component_key']]);
        $relationships = DB::table(self::TWIN_RELATIONSHIPS)
            ->where('digital_twin_revision_id', $revision->id)
            ->orderBy('relationship_type')
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $relationship): array => [
                'id' => (string) $relationship->id,
                'from' => (string) $componentKeys->get((string) $relationship->source_component_id),
                'to' => (string) $componentKeys->get((string) $relationship->target_component_id),
                'relationship_type' => (string) $relationship->relationship_type,
                'properties' => $this->decode($relationship->properties),
            ])->all();
        $topology = [
            'nodes' => array_map(fn (array $component): array => [
                'id' => $component['component_key'],
                'label' => $component['name_ar'],
                'kind' => $component['ownership_scope'],
                'ownership_scope' => $component['ownership_scope'],
                'enterprise_entity_id' => $component['enterprise_entity_id'],
                'enterprise_entity_revision_id' => $component['enterprise_entity_revision_id'] ?? null,
                'device_template_revision_id' => $component['device_template_revision_id'],
                'simulation_definition' => $component['simulation_definition'],
            ], $components),
            'links' => array_map(fn (array $relationship): array => [
                'id' => $relationship['id'],
                'from' => $relationship['from'],
                'to' => $relationship['to'],
                'enterprise_relationship_id' => $relationship['enterprise_relationship_id'] ?? null,
                'enterprise_relationship_revision_id' => $relationship['enterprise_relationship_revision_id'] ?? null,
                'type' => $relationship['relationship_type'],
                'properties' => $relationship['properties'],
            ], $relationships),
        ];

        return [
            'digital_twin_id' => (string) $revision->digital_twin_id,
            'revision' => (int) $revision->revision,
            'topology' => $topology,
            'behavior_model' => $this->decode($revision->behavior_model),
            'components' => $components,
            'relationships' => $relationships,
        ];
    }

    /** @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function deviceDefinition(array $definition): array
    {
        return [
            'capabilities' => $this->stringList($definition['capabilities'] ?? []),
            'state_model' => $this->arrayValue($definition['state_model'] ?? []),
            'actions' => $this->arrayValue($definition['actions'] ?? []),
            'events' => $this->arrayValue($definition['events'] ?? []),
            'telemetry' => $this->arrayValue($definition['telemetry'] ?? []),
            'behavior_rules' => $this->arrayValue($definition['behavior_rules'] ?? []),
            'validation_hooks' => $this->arrayValue($definition['validation_hooks'] ?? []),
        ];
    }

    /** @return array<string, mixed> */
    private function deviceRevisionPayload(stdClass $revision): array
    {
        return [
            'device_template_id' => (string) $revision->device_template_id,
            'revision' => (int) $revision->revision,
            'capabilities' => $this->decode($revision->capabilities),
            'state_model' => $this->decode($revision->state_model),
            'actions' => $this->decode($revision->actions),
            'events' => $this->decode($revision->events),
            'telemetry' => $this->decode($revision->telemetry),
            'behavior_rules' => $this->decode($revision->behavior_rules),
            'validation_hooks' => $this->decode($revision->validation_hooks),
        ];
    }

    /** @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function jsonColumns(array $payload): array
    {
        $columns = [];
        foreach (['capabilities', 'state_model', 'actions', 'events', 'telemetry', 'behavior_rules', 'validation_hooks'] as $key) {
            $columns[$key] = $this->json($this->arrayValue($payload[$key] ?? []));
        }

        return $columns;
    }

    private function requireOwnedEntity(string $enterpriseId, string $entityId): stdClass
    {
        $entity = DB::table(self::ENTITIES)
            ->where('id', $entityId)
            ->where('enterprise_id', $enterpriseId)
            ->first();
        if ($entity === null) {
            throw new DomainException('Enterprise Entity is not owned by the supplied Enterprise.');
        }

        return $entity;
    }

    private function requireTwinComponent(string $revisionId, string $componentId): stdClass
    {
        $component = DB::table(self::TWIN_COMPONENTS)
            ->where('id', $componentId)
            ->where('digital_twin_revision_id', $revisionId)
            ->first();
        if ($component === null) {
            throw new DomainException('Digital Twin relationship endpoints must belong to the same revision.');
        }

        return $component;
    }

    /** @param list<string> $statuses */
    private function requireDefinitionRevision(string $table, string $id, array $statuses): stdClass
    {
        $revision = DB::table($table)->where('id', $id)->first();
        if ($revision === null) {
            throw new DomainException('Definition revision not found.');
        }
        if (! in_array((string) $revision->status, $statuses, true)) {
            throw new LogicException('Definition revision is not editable in its current lifecycle state.');
        }

        return $revision;
    }

    private function assertValidatedDigest(stdClass $revision, string $digest): void
    {
        $report = $this->decode($revision->validation_report);
        if (($report['valid'] ?? false) !== true || ! is_string($report['validated_digest'] ?? null) || ! hash_equals($report['validated_digest'], $digest)) {
            throw new LogicException('Definition changed after validation; validate the draft again before publication.');
        }
    }

    /** @param list<string> $errors
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function validationReport(array $errors, string $digest, string $actorId, array $warnings = []): array
    {
        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'validated_digest' => $digest,
            'validated_by' => $actorId,
            'checked_at' => now()->toISOString(),
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function requiredString(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException("{$key} is required.");
        }

        return trim($value);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** @return array<string, mixed> */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && trim($item) !== ''));
    }

    private function assertActor(string $actorId): void
    {
        if (trim($actorId) === '') {
            throw new DomainException('Definition mutations require an attributed actor.');
        }
    }

    /** @return array<string, mixed> */
    private function row(string $table, string $id): array
    {
        return (array) $this->requireRow($table, $id);
    }

    private function requireRow(string $table, string $id): stdClass
    {
        $row = DB::table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new DomainException("Required definition record not found in {$table}.");
        }

        return $row;
    }

    /** @return array<string, mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<array-key, mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }

    /**
     * @param string $revisionId
     * @param string $actorId
     * @return array<string, mixed>
     */
    public function createBaseline(string $revisionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($revisionId, $actorId): array {
            $revision = DB::table(self::TWIN_REVISIONS)
                ->where('id', $revisionId)
                ->where('status', 'PUBLISHED')
                ->first();

            if ($revision === null) {
                throw new LogicException('Digital Twin revision must be published to create a baseline.');
            }
            
            // Lock the stable twin identity to ensure race-safe monotonic revision across any twin revisions
            DB::table(self::DIGITAL_TWINS)
                ->where('id', $revision->digital_twin_id)
                ->lockForUpdate()
                ->first();
            
            $baselineId = (string) Str::uuid7();
            $now = now();
            
            $nextRevision = (int) DB::table('simulation_baselines')
                ->where('digital_twin_id', $revision->digital_twin_id)
                ->max('revision') + 1;
                
            $payload = $this->twinRevisionPayload($revision);
            
            $capabilities = [];
            $components = [];
            foreach ($this->arrayValue($payload['components']) as $component) {
                $componentCap = [];
                if (isset($component['device_template_revision_id']) && $component['device_template_revision_id'] !== null) {
                    $templateRev = DB::table(self::DEVICE_REVISIONS)
                        ->where('id', $component['device_template_revision_id'])
                        ->where('enterprise_id', $revision->enterprise_id)
                        ->where('status', 'PUBLISHED')
                        ->first();
                        
                    if ($templateRev === null) {
                         throw new LogicException('Missing or non-PUBLISHED pinned device template revision.');
                    }
                    if (isset($templateRev->capabilities)) {
                        $caps = $this->decode($templateRev->capabilities);
                        if (is_array($caps)) {
                            $componentCap = $caps;
                            $capabilities = array_merge($capabilities, $caps);
                        }
                    }
                }
                $compCopy = $component;
                $compCopy['resolved_capabilities'] = $componentCap;
                // Remove device_template if any to prevent non-existent nested assumptions
                unset($compCopy['device_template']);
                $components[] = $compCopy;
            }
            $capabilities = array_values(array_unique($capabilities));
            sort($capabilities);

            $state = [
                'enterprise_id' => $revision->enterprise_id,
                'digital_twin_id' => $revision->digital_twin_id,
                'digital_twin_revision_id' => $revision->id,
                'digital_twin_revision' => $revision->revision,
                'components' => $components,
                'relationships' => $this->arrayValue($payload['relationships']),
                'capabilities' => $capabilities,
            ];
            
            $digest = $this->digest($state);
            
            DB::table('simulation_baselines')->insert([
                'id' => $baselineId,
                'enterprise_id' => $revision->enterprise_id,
                'digital_twin_id' => $revision->digital_twin_id,
                'digital_twin_revision_id' => $revision->id,
                'revision' => $nextRevision,
                'status' => 'PUBLISHED',
                'digest' => $digest,
                'state' => $this->json($state),
                'published_at' => $now,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            return $this->row('simulation_baselines', $baselineId);
        });
    }

}
