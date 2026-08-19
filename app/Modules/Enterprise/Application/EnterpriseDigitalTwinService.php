<?php

namespace App\Modules\Enterprise\Application;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use stdClass;

final class EnterpriseDigitalTwinService
{
    /** @var list<string> */
    public const ENTITY_TYPES = [
        'SYSTEM',
        'APPLICATION',
        'SERVICE',
        'NETWORK',
        'DEVICE',
        'IDENTITY',
        'DATA',
        'SECURITY_CONTROL',
        'TEAM',
        'ROLE',
    ];

    /** @var list<string> */
    public const RELATIONSHIP_TYPES = [
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

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function createEnterprise(array $attributes): array
    {
        $id = (string) Str::uuid7();
        $now = now();

        DB::table('simulation_enterprises')->insert([
            'id' => $id,
            'slug' => $this->requiredString($attributes, 'slug'),
            'name_ar' => $this->requiredString($attributes, 'name_ar'),
            'name_en' => $this->optionalString($attributes, 'name_en'),
            'description_ar' => $this->optionalString($attributes, 'description_ar'),
            'definition' => $this->json([
                'model' => 'CANONICAL_ENTERPRISE_V1',
                'properties' => $this->arrayAttribute($attributes, 'properties'),
            ]),
            'is_fixture' => (bool) ($attributes['is_fixture'] ?? false),
            'created_by' => $this->optionalString($attributes, 'created_by'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->enterprise($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function addEntity(string $enterpriseId, array $attributes): array
    {
        $this->requireRow('simulation_enterprises', $enterpriseId);
        $entityType = strtoupper($this->requiredString($attributes, 'entity_type'));

        if (in_array($entityType, self::ENTITY_TYPES, true) === false) {
            throw new InvalidArgumentException("Unsupported canonical Enterprise entity type: {$entityType}.");
        }

        $lifecycle = strtoupper((string) ($attributes['lifecycle_state'] ?? 'ACTIVE'));
        if (in_array($lifecycle, ['ACTIVE', 'RETIRED'], true) === false) {
            throw new InvalidArgumentException('Enterprise entity lifecycle must be ACTIVE or RETIRED.');
        }

        $id = (string) Str::uuid7();
        $now = now();

        DB::table('enterprise_entities')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'stable_key' => $this->requiredString($attributes, 'stable_key'),
            'entity_type' => $entityType,
            'lifecycle_state' => $lifecycle,
            'name_ar' => $this->requiredString($attributes, 'name_ar'),
            'name_en' => $this->optionalString($attributes, 'name_en'),
            'properties' => $this->json($this->arrayAttribute($attributes, 'properties')),
            'revision_provenance' => $this->json($this->arrayAttribute($attributes, 'revision_provenance')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->entity($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function addRelationship(string $enterpriseId, array $attributes): array
    {
        $sourceId = $this->requiredString($attributes, 'source_entity_id');
        $targetId = $this->requiredString($attributes, 'target_entity_id');
        $type = strtoupper($this->requiredString($attributes, 'relationship_type'));

        if (in_array($type, self::RELATIONSHIP_TYPES, true) === false) {
            throw new InvalidArgumentException("Unsupported Enterprise relationship type: {$type}.");
        }

        $source = $this->requireEntityInEnterprise($enterpriseId, $sourceId);
        $target = $this->requireEntityInEnterprise($enterpriseId, $targetId);

        if ((string) $source->enterprise_id !== (string) $target->enterprise_id) {
            throw new DomainException('Enterprise relationships cannot cross canonical Enterprise aggregates.');
        }

        $id = (string) Str::uuid7();
        $now = now();

        DB::table('enterprise_relationships')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'source_entity_id' => $sourceId,
            'target_entity_id' => $targetId,
            'relationship_type' => $type,
            'properties' => $this->json($this->arrayAttribute($attributes, 'properties')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->relationship($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function createDigitalTwin(string $enterpriseId, array $attributes): array
    {
        $this->requireRow('simulation_enterprises', $enterpriseId);
        $id = (string) Str::uuid7();
        $now = now();

        DB::table('enterprise_digital_twins')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'slug' => $this->requiredString($attributes, 'slug'),
            'name_ar' => $this->requiredString($attributes, 'name_ar'),
            'name_en' => $this->optionalString($attributes, 'name_en'),
            'purpose' => $this->optionalString($attributes, 'purpose'),
            'lifecycle_state' => 'ACTIVE',
            'created_by' => $this->optionalString($attributes, 'created_by'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->digitalTwin($id);
    }

    /** @return list<array<string, mixed>> */
    public function listDigitalTwins(string $enterpriseId): array
    {
        $this->requireRow('simulation_enterprises', $enterpriseId);

        return DB::table('enterprise_digital_twins')
            ->where('enterprise_id', $enterpriseId)
            ->orderBy('name_ar')
            ->get()
            ->map(function (stdClass $twin): array {
                $latest = DB::table('simulation_digital_twin_revisions')
                    ->where('digital_twin_id', $twin->id)
                    ->orderByDesc('revision')
                    ->first();

                return [
                    'id' => (string) $twin->id,
                    'enterprise_id' => (string) $twin->enterprise_id,
                    'slug' => (string) $twin->slug,
                    'name_ar' => (string) $twin->name_ar,
                    'name_en' => $twin->name_en === null ? null : (string) $twin->name_en,
                    'purpose' => $twin->purpose === null ? null : (string) $twin->purpose,
                    'lifecycle_state' => (string) $twin->lifecycle_state,
                    'latest_revision' => $latest === null ? null : [
                        'id' => (string) $latest->id,
                        'revision' => (int) $latest->revision,
                        'status' => (string) $latest->status,
                        'published_at' => $latest->published_at === null ? null : (string) $latest->published_at,
                    ],
                ];
            })
            ->all();
    }

    /**
     * Revisions keep the existing Enterprise-wide numeric sequence for compatibility
     * with the W03 runtime tables while digital_twin_id provides the canonical Twin identity.
     *
     * @param  list<string>  $enterpriseEntityIds
     * @param  list<array<string, mixed>>  $simulationLocalObjects
     * @param  list<array<string, mixed>>  $simulationRelationships
     * @param  array<string, mixed>  $behaviorModel
     * @return array<string, mixed>
     */
    public function createDraftRevision(
        string $digitalTwinId,
        array $enterpriseEntityIds,
        array $simulationLocalObjects = [],
        array $simulationRelationships = [],
        array $behaviorModel = [],
        ?string $sourceRevisionId = null,
        ?string $actorId = null,
    ): array {
        $twin = $this->requireRow('enterprise_digital_twins', $digitalTwinId);
        $enterpriseId = (string) $twin->enterprise_id;

        if ($sourceRevisionId !== null) {
            $source = $this->requireRow('simulation_digital_twin_revisions', $sourceRevisionId);
            if (
                (string) ($source->digital_twin_id ?? '') !== $digitalTwinId
                || (string) $source->status !== 'PUBLISHED'
            ) {
                throw new DomainException('A new draft may only clone a published revision of the same Digital Twin.');
            }
        }

        $model = $this->buildDraftModel(
            $enterpriseId,
            $enterpriseEntityIds,
            $simulationLocalObjects,
            $simulationRelationships,
            $behaviorModel,
        );

        $id = (string) Str::uuid7();
        $revision = (int) DB::table('simulation_digital_twin_revisions')
            ->where('enterprise_id', $enterpriseId)
            ->max('revision') + 1;
        $now = now();

        DB::table('simulation_digital_twin_revisions')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'digital_twin_id' => $digitalTwinId,
            'source_revision_id' => $sourceRevisionId,
            'revision' => $revision,
            'status' => 'DRAFT',
            'topology' => $this->json($model['topology']),
            'behavior_model' => $this->json($behaviorModel),
            'simulation_local_objects' => $this->json($model['simulation_local_objects']),
            'validation_report' => null,
            'validated_at' => null,
            'digest' => $this->digest($model),
            'published_at' => null,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->revision($id);
    }

    /**
     * @param  list<string>  $enterpriseEntityIds
     * @param  list<array<string, mixed>>  $simulationLocalObjects
     * @param  list<array<string, mixed>>  $simulationRelationships
     * @param  array<string, mixed>  $behaviorModel
     * @return array<string, mixed>
     */
    public function updateDraftRevision(
        string $revisionId,
        array $enterpriseEntityIds,
        array $simulationLocalObjects = [],
        array $simulationRelationships = [],
        array $behaviorModel = [],
    ): array {
        $revision = $this->requireDraftRevision($revisionId);
        $model = $this->buildDraftModel(
            (string) $revision->enterprise_id,
            $enterpriseEntityIds,
            $simulationLocalObjects,
            $simulationRelationships,
            $behaviorModel,
        );

        DB::table('simulation_digital_twin_revisions')
            ->where('id', $revisionId)
            ->where('status', 'DRAFT')
            ->update([
                'topology' => $this->json($model['topology']),
                'behavior_model' => $this->json($behaviorModel),
                'simulation_local_objects' => $this->json($model['simulation_local_objects']),
                'validation_report' => null,
                'validated_at' => null,
                'digest' => $this->digest($model),
                'updated_at' => now(),
            ]);

        return $this->revision($revisionId);
    }

    /** @return array<string, mixed> */
    public function validateDraftRevision(string $revisionId): array
    {
        $revision = $this->requireDraftRevision($revisionId);
        $topology = $this->decodeJson($revision->topology);
        $enterpriseIds = $this->stringList($topology['enterprise_entity_ids'] ?? []);
        $relationshipIds = $this->stringList($topology['relationship_ids'] ?? []);
        $localObjects = $this->decodeJson($revision->simulation_local_objects);
        $localRelationships = $this->arrayList($topology['simulation_relationships'] ?? []);

        $existingEntities = DB::table('enterprise_entities')
            ->where('enterprise_id', $revision->enterprise_id)
            ->whereIn('id', $enterpriseIds)
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();
        $entityReferencesValid = count($existingEntities) === count(array_unique($enterpriseIds));

        $canonicalRelationships = DB::table('enterprise_relationships')
            ->where('enterprise_id', $revision->enterprise_id)
            ->whereIn('id', $relationshipIds)
            ->get();
        $canonicalRelationshipReferencesValid = $canonicalRelationships->count() === count(array_unique($relationshipIds));
        $selected = array_fill_keys($enterpriseIds, true);
        $canonicalRelationshipEndpointsValid = $canonicalRelationships->every(
            fn (stdClass $relationship): bool => isset(
                $selected[(string) $relationship->source_entity_id],
                $selected[(string) $relationship->target_entity_id],
            ),
        );

        $localIds = [];
        $localObjectsExplicit = true;
        foreach ($localObjects as $object) {
            if (
                is_array($object) === false
                || ($object['scope'] ?? null) !== 'SIMULATION_LOCAL'
                || is_string($object['id'] ?? null) === false
            ) {
                $localObjectsExplicit = false;
                continue;
            }
            $localIds[] = (string) $object['id'];
        }
        $localIdsUnique = count($localIds) === count(array_unique($localIds));

        $allowedEndpoints = $selected + array_fill_keys($localIds, true);
        $simulationRelationshipsValid = true;
        foreach ($localRelationships as $relationship) {
            if (
                ($relationship['scope'] ?? null) !== 'SIMULATION_LOCAL'
                || in_array((string) ($relationship['relationship_type'] ?? ''), self::RELATIONSHIP_TYPES, true) === false
                || isset(
                    $allowedEndpoints[(string) ($relationship['source_id'] ?? '')],
                    $allowedEndpoints[(string) ($relationship['target_id'] ?? '')],
                ) === false
            ) {
                $simulationRelationshipsValid = false;
            }
        }

        $checks = [
            'canonical_entity_references' => $entityReferencesValid,
            'canonical_relationship_references' => $canonicalRelationshipReferencesValid,
            'canonical_relationship_endpoints' => $canonicalRelationshipEndpointsValid,
            'simulation_local_objects_explicit' => $localObjectsExplicit && $localIdsUnique,
            'simulation_local_relationships_typed' => $simulationRelationshipsValid,
        ];
        $valid = in_array(false, $checks, true) === false;
        $report = [
            'valid' => $valid,
            'checked_at' => now()->toISOString(),
            'checks' => $checks,
            'counts' => [
                'enterprise_entities' => count($enterpriseIds),
                'canonical_relationships' => count($relationshipIds),
                'simulation_local_objects' => count($localIds),
                'simulation_local_relationships' => count($localRelationships),
            ],
        ];

        DB::table('simulation_digital_twin_revisions')
            ->where('id', $revisionId)
            ->where('status', 'DRAFT')
            ->update([
                'validation_report' => $this->json($report),
                'validated_at' => $valid ? now() : null,
                'updated_at' => now(),
            ]);

        return $report;
    }

    /** @return array<string, mixed> */
    public function publishRevision(string $revisionId): array
    {
        $revision = $this->requireDraftRevision($revisionId);
        $report = $this->decodeJson($revision->validation_report);

        if (($report['valid'] ?? false) !== true || $revision->validated_at === null) {
            throw new DomainException('Digital Twin draft must pass validation before publication.');
        }

        $updated = DB::table('simulation_digital_twin_revisions')
            ->where('id', $revisionId)
            ->where('status', 'DRAFT')
            ->update([
                'status' => 'PUBLISHED',
                'published_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new DomainException('Digital Twin revision publication lost its draft precondition.');
        }

        return $this->revision($revisionId);
    }

    /** @return array<string, mixed> */
    public function createDraftFromRevision(string $publishedRevisionId, ?string $actorId = null): array
    {
        $source = $this->requireRow('simulation_digital_twin_revisions', $publishedRevisionId);
        $digitalTwinId = $source->digital_twin_id === null ? null : (string) $source->digital_twin_id;

        if ((string) $source->status !== 'PUBLISHED' || $digitalTwinId === null || $digitalTwinId === '') {
            throw new DomainException('Only a canonical published Digital Twin revision can be cloned as a new draft.');
        }

        $topology = $this->decodeJson($source->topology);

        return $this->createDraftRevision(
            $digitalTwinId,
            $this->stringList($topology['enterprise_entity_ids'] ?? []),
            $this->arrayList($this->decodeJson($source->simulation_local_objects)),
            $this->arrayList($topology['simulation_relationships'] ?? []),
            $this->decodeJson($source->behavior_model),
            $publishedRevisionId,
            $actorId,
        );
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function createBaselineFromRevision(
        string $publishedRevisionId,
        array $state,
        ?string $actorId = null,
    ): array {
        $revision = $this->requireRow('simulation_digital_twin_revisions', $publishedRevisionId);
        $digitalTwinId = $revision->digital_twin_id === null ? null : (string) $revision->digital_twin_id;

        if ((string) $revision->status !== 'PUBLISHED' || $digitalTwinId === null || $digitalTwinId === '') {
            throw new DomainException('A Baseline must pin a canonical published Digital Twin revision.');
        }

        $enterpriseId = (string) $revision->enterprise_id;
        $baselineRevision = (int) DB::table('simulation_baselines')
            ->where('enterprise_id', $enterpriseId)
            ->max('revision') + 1;
        $id = (string) Str::uuid7();
        $now = now();
        $payload = [
            'digital_twin_revision_id' => $publishedRevisionId,
            'digital_twin_revision_digest' => (string) $revision->digest,
            'state' => $state,
        ];

        DB::table('simulation_baselines')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'digital_twin_id' => $digitalTwinId,
            'digital_twin_revision_id' => $publishedRevisionId,
            'revision' => $baselineRevision,
            'status' => 'PUBLISHED',
            'state' => $this->json($state),
            'digest' => $this->digest($payload),
            'published_at' => $now,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->baseline($id);
    }

    /**
     * Enterprise Catalog is deliberately computed from canonical records and has no
     * independent persistence model.
     *
     * @return array<string, mixed>
     */
    public function catalogProjection(string $enterpriseId): array
    {
        $enterprise = $this->enterprise($enterpriseId);
        $entities = DB::table('enterprise_entities')
            ->where('enterprise_id', $enterpriseId)
            ->orderBy('entity_type')
            ->orderBy('stable_key')
            ->get()
            ->map(fn (stdClass $entity): array => $this->entityArray($entity))
            ->all();
        $relationships = DB::table('enterprise_relationships')
            ->where('enterprise_id', $enterpriseId)
            ->orderBy('relationship_type')
            ->get()
            ->map(fn (stdClass $relationship): array => $this->relationshipArray($relationship))
            ->all();

        $grouped = [];
        foreach (self::ENTITY_TYPES as $type) {
            $grouped[$type] = array_values(array_filter(
                $entities,
                fn (array $entity): bool => $entity['entity_type'] === $type,
            ));
        }

        return [
            'projection_only' => true,
            'canonical_owner' => 'Enterprise',
            'enterprise' => $enterprise,
            'entities_by_type' => $grouped,
            'relationships' => $relationships,
        ];
    }

    /** @return array<string, mixed> */
    public function operationalModel(string $revisionId): array
    {
        $revision = $this->requireRow('simulation_digital_twin_revisions', $revisionId);
        $topology = $this->decodeJson($revision->topology);
        $entityIds = $this->stringList($topology['enterprise_entity_ids'] ?? []);
        $relationshipIds = $this->stringList($topology['relationship_ids'] ?? []);

        $enterpriseNodes = DB::table('enterprise_entities')
            ->where('enterprise_id', $revision->enterprise_id)
            ->whereIn('id', $entityIds)
            ->orderBy('stable_key')
            ->get()
            ->map(function (stdClass $entity): array {
                $node = $this->entityArray($entity);
                $node['origin'] = 'ENTERPRISE';

                return $node;
            })
            ->all();

        $simulationNodes = array_map(
            function (array $object): array {
                $object['origin'] = 'SIMULATION_LOCAL';

                return $object;
            },
            $this->arrayList($this->decodeJson($revision->simulation_local_objects)),
        );

        $canonicalEdges = DB::table('enterprise_relationships')
            ->where('enterprise_id', $revision->enterprise_id)
            ->whereIn('id', $relationshipIds)
            ->orderBy('relationship_type')
            ->get()
            ->map(function (stdClass $relationship): array {
                $edge = $this->relationshipArray($relationship);
                $edge['origin'] = 'ENTERPRISE';

                return $edge;
            })
            ->all();

        $simulationEdges = array_map(
            function (array $relationship): array {
                $relationship['origin'] = 'SIMULATION_LOCAL';

                return $relationship;
            },
            $this->arrayList($topology['simulation_relationships'] ?? []),
        );

        return [
            'digital_twin_id' => $revision->digital_twin_id === null ? null : (string) $revision->digital_twin_id,
            'revision_id' => (string) $revision->id,
            'revision' => (int) $revision->revision,
            'status' => (string) $revision->status,
            'nodes' => array_values([...$enterpriseNodes, ...$simulationNodes]),
            'edges' => array_values([...$canonicalEdges, ...$simulationEdges]),
            'behavior_model' => $this->decodeJson($revision->behavior_model),
        ];
    }

    /** @return array<string, mixed> */
    public function inspectRevision(string $revisionId): array
    {
        $revision = $this->revision($revisionId);
        $revision['operational_model'] = $this->operationalModel($revisionId);

        return $revision;
    }

    /**
     * @param  list<string>  $enterpriseEntityIds
     * @param  list<array<string, mixed>>  $simulationLocalObjects
     * @param  list<array<string, mixed>>  $simulationRelationships
     * @param  array<string, mixed>  $behaviorModel
     * @return array{topology:array<string,mixed>,simulation_local_objects:list<array<string,mixed>>,behavior_model:array<string,mixed>}
     */
    private function buildDraftModel(
        string $enterpriseId,
        array $enterpriseEntityIds,
        array $simulationLocalObjects,
        array $simulationRelationships,
        array $behaviorModel,
    ): array {
        $entityIds = array_values(array_unique($enterpriseEntityIds));
        $this->assertEntitySelection($enterpriseId, $entityIds);
        $localObjects = $this->normalizeLocalObjects($entityIds, $simulationLocalObjects);
        $localIds = array_map(
            fn (array $object): string => (string) $object['id'],
            $localObjects,
        );
        $simulationEdges = $this->normalizeSimulationRelationships(
            [...$entityIds, ...$localIds],
            $simulationRelationships,
        );

        $relationshipIds = DB::table('enterprise_relationships')
            ->where('enterprise_id', $enterpriseId)
            ->whereIn('source_entity_id', $entityIds)
            ->whereIn('target_entity_id', $entityIds)
            ->orderBy('relationship_type')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        return [
            'topology' => [
                'enterprise_entity_ids' => $entityIds,
                'relationship_ids' => $relationshipIds,
                'simulation_relationships' => $simulationEdges,
            ],
            'simulation_local_objects' => $localObjects,
            'behavior_model' => $behaviorModel,
        ];
    }

    /** @param list<string> $entityIds */
    private function assertEntitySelection(string $enterpriseId, array $entityIds): void
    {
        if ($entityIds === []) {
            throw new InvalidArgumentException('A Digital Twin draft must reference at least one canonical Enterprise entity.');
        }

        $count = DB::table('enterprise_entities')
            ->where('enterprise_id', $enterpriseId)
            ->whereIn('id', $entityIds)
            ->count();

        if ($count !== count($entityIds)) {
            throw new DomainException('Digital Twin drafts may reference only canonical entities from their Enterprise.');
        }
    }

    /**
     * @param  list<string>  $canonicalEntityIds
     * @param  list<array<string, mixed>>  $objects
     * @return list<array<string, mixed>>
     */
    private function normalizeLocalObjects(array $canonicalEntityIds, array $objects): array
    {
        $normalized = [];
        $seen = array_fill_keys($canonicalEntityIds, true);

        foreach ($objects as $object) {
            $id = is_string($object['id'] ?? null) && trim((string) $object['id']) !== ''
                ? trim((string) $object['id'])
                : (string) Str::uuid7();

            if (isset($seen[$id])) {
                throw new InvalidArgumentException('Simulation-local object identifiers must be unique and cannot shadow canonical Enterprise entities.');
            }
            $seen[$id] = true;

            $type = is_string($object['object_type'] ?? null) ? trim((string) $object['object_type']) : '';
            $name = is_string($object['name'] ?? null) ? trim((string) $object['name']) : '';
            if ($type === '' || $name === '') {
                throw new InvalidArgumentException('Simulation-local objects require object_type and name.');
            }

            $normalized[] = [
                'id' => $id,
                'scope' => 'SIMULATION_LOCAL',
                'object_type' => $type,
                'name' => $name,
                'properties' => is_array($object['properties'] ?? null) ? $object['properties'] : [],
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $allowedEndpointIds
     * @param  list<array<string, mixed>>  $relationships
     * @return list<array<string, mixed>>
     */
    private function normalizeSimulationRelationships(array $allowedEndpointIds, array $relationships): array
    {
        $allowed = array_fill_keys($allowedEndpointIds, true);
        $normalized = [];

        foreach ($relationships as $relationship) {
            $sourceId = is_string($relationship['source_id'] ?? null) ? (string) $relationship['source_id'] : '';
            $targetId = is_string($relationship['target_id'] ?? null) ? (string) $relationship['target_id'] : '';
            $type = is_string($relationship['relationship_type'] ?? null)
                ? strtoupper((string) $relationship['relationship_type'])
                : '';

            if (
                isset($allowed[$sourceId], $allowed[$targetId]) === false
                || in_array($type, self::RELATIONSHIP_TYPES, true) === false
            ) {
                throw new InvalidArgumentException('Simulation-local relationships require valid endpoints and an approved typed relationship.');
            }

            $normalized[] = [
                'id' => is_string($relationship['id'] ?? null) && trim((string) $relationship['id']) !== ''
                    ? trim((string) $relationship['id'])
                    : (string) Str::uuid7(),
                'scope' => 'SIMULATION_LOCAL',
                'relationship_type' => $type,
                'source_id' => $sourceId,
                'target_id' => $targetId,
                'properties' => is_array($relationship['properties'] ?? null) ? $relationship['properties'] : [],
            ];
        }

        return $normalized;
    }

    private function requireDraftRevision(string $revisionId): stdClass
    {
        $revision = $this->requireRow('simulation_digital_twin_revisions', $revisionId);

        if ((string) $revision->status !== 'DRAFT' || $revision->digital_twin_id === null) {
            throw new DomainException('Digital Twin revision must be a canonical DRAFT for this operation.');
        }

        return $revision;
    }

    private function requireEntityInEnterprise(string $enterpriseId, string $entityId): stdClass
    {
        $entity = DB::table('enterprise_entities')
            ->where('id', $entityId)
            ->where('enterprise_id', $enterpriseId)
            ->first();

        if ($entity === null) {
            throw new DomainException('Canonical Enterprise entity was not found in the requested Enterprise.');
        }

        return $entity;
    }

    /** @return array<string, mixed> */
    private function enterprise(string $id): array
    {
        $row = $this->requireRow('simulation_enterprises', $id);

        return [
            'id' => (string) $row->id,
            'slug' => (string) $row->slug,
            'name_ar' => (string) $row->name_ar,
            'name_en' => $row->name_en === null ? null : (string) $row->name_en,
            'description_ar' => $row->description_ar === null ? null : (string) $row->description_ar,
            'definition' => $this->decodeJson($row->definition),
            'is_fixture' => (bool) $row->is_fixture,
        ];
    }

    /** @return array<string, mixed> */
    private function entity(string $id): array
    {
        return $this->entityArray($this->requireRow('enterprise_entities', $id));
    }

    /** @return array<string, mixed> */
    private function entityArray(stdClass $row): array
    {
        return [
            'id' => (string) $row->id,
            'enterprise_id' => (string) $row->enterprise_id,
            'stable_key' => (string) $row->stable_key,
            'entity_type' => (string) $row->entity_type,
            'lifecycle_state' => (string) $row->lifecycle_state,
            'name_ar' => (string) $row->name_ar,
            'name_en' => $row->name_en === null ? null : (string) $row->name_en,
            'properties' => $this->decodeJson($row->properties),
            'revision_provenance' => $this->decodeJson($row->revision_provenance),
        ];
    }

    /** @return array<string, mixed> */
    private function relationship(string $id): array
    {
        return $this->relationshipArray($this->requireRow('enterprise_relationships', $id));
    }

    /** @return array<string, mixed> */
    private function relationshipArray(stdClass $row): array
    {
        return [
            'id' => (string) $row->id,
            'enterprise_id' => (string) $row->enterprise_id,
            'source_entity_id' => (string) $row->source_entity_id,
            'target_entity_id' => (string) $row->target_entity_id,
            'relationship_type' => (string) $row->relationship_type,
            'properties' => $this->decodeJson($row->properties),
        ];
    }

    /** @return array<string, mixed> */
    private function digitalTwin(string $id): array
    {
        $row = $this->requireRow('enterprise_digital_twins', $id);

        return [
            'id' => (string) $row->id,
            'enterprise_id' => (string) $row->enterprise_id,
            'slug' => (string) $row->slug,
            'name_ar' => (string) $row->name_ar,
            'name_en' => $row->name_en === null ? null : (string) $row->name_en,
            'purpose' => $row->purpose === null ? null : (string) $row->purpose,
            'lifecycle_state' => (string) $row->lifecycle_state,
        ];
    }

    /** @return array<string, mixed> */
    private function revision(string $id): array
    {
        $row = $this->requireRow('simulation_digital_twin_revisions', $id);

        return [
            'id' => (string) $row->id,
            'enterprise_id' => (string) $row->enterprise_id,
            'digital_twin_id' => $row->digital_twin_id === null ? null : (string) $row->digital_twin_id,
            'source_revision_id' => $row->source_revision_id === null ? null : (string) $row->source_revision_id,
            'revision' => (int) $row->revision,
            'status' => (string) $row->status,
            'topology' => $this->decodeJson($row->topology),
            'behavior_model' => $this->decodeJson($row->behavior_model),
            'simulation_local_objects' => $this->decodeJson($row->simulation_local_objects),
            'validation_report' => $this->decodeJson($row->validation_report),
            'validated_at' => $row->validated_at === null ? null : (string) $row->validated_at,
            'digest' => (string) $row->digest,
            'published_at' => $row->published_at === null ? null : (string) $row->published_at,
        ];
    }

    /** @return array<string, mixed> */
    private function baseline(string $id): array
    {
        $row = $this->requireRow('simulation_baselines', $id);

        return [
            'id' => (string) $row->id,
            'enterprise_id' => (string) $row->enterprise_id,
            'digital_twin_id' => $row->digital_twin_id === null ? null : (string) $row->digital_twin_id,
            'digital_twin_revision_id' => (string) $row->digital_twin_revision_id,
            'revision' => (int) $row->revision,
            'status' => (string) $row->status,
            'state' => $this->decodeJson($row->state),
            'digest' => (string) $row->digest,
            'published_at' => $row->published_at === null ? null : (string) $row->published_at,
        ];
    }

    private function requireRow(string $table, string $id): stdClass
    {
        $row = DB::table($table)->where('id', $id)->first();

        if ($row === null) {
            throw new DomainException("Required Enterprise record was not found in {$table}.");
        }

        return $row;
    }

    /** @param array<string, mixed> $attributes */
    private function requiredString(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? null;

        if (is_string($value) === false || trim($value) === '') {
            throw new InvalidArgumentException("{$key} is required.");
        }

        return trim($value);
    }

    /** @param array<string, mixed> $attributes */
    private function optionalString(array $attributes, string $key): ?string
    {
        $value = $attributes[$key] ?? null;

        if ($value === null) {
            return null;
        }
        if (is_string($value) === false) {
            throw new InvalidArgumentException("{$key} must be a string when provided.");
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function arrayAttribute(array $attributes, string $key): array
    {
        $value = $attributes[$key] ?? [];

        if (is_array($value) === false) {
            throw new InvalidArgumentException("{$key} must be an array.");
        }

        return $value;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (is_array($value) === false) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                fn (mixed $item): string => is_string($item) ? $item : '',
                $value,
            ),
            fn (string $item): bool => $item !== '',
        ));
    }

    /** @return list<array<string, mixed>> */
    private function arrayList(mixed $value): array
    {
        if (is_array($value) === false) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }

    /** @return array<string, mixed> */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) === false || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private function json(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', $this->json($this->canonicalize($value)));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (is_array($value) === false) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->canonicalize($item),
                $value,
            );
        }

        ksort($value, SORT_STRING);

        return array_map(
            fn (mixed $item): mixed => $this->canonicalize($item),
            $value,
        );
    }
}
