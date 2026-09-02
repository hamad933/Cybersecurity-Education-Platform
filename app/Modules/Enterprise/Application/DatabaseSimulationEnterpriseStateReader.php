<?php

namespace App\Modules\Enterprise\Application;

use Illuminate\Support\Facades\DB;
use stdClass;

final class DatabaseSimulationEnterpriseStateReader implements SimulationEnterpriseStateReader
{
    private const DIGITAL_TWINS_TABLE = 'simulation_digital_twins';

    private const ENTITIES_TABLE = 'simulation_enterprise_entities';

    private const ENTERPRISE_RELATIONSHIPS_TABLE = 'simulation_enterprise_relationships';

    private const DEVICE_TEMPLATES_TABLE = 'simulation_device_templates';

    private const DEVICE_REVISIONS_TABLE = 'simulation_device_template_revisions';

    private const TWIN_COMPONENTS_TABLE = 'simulation_digital_twin_components';

    private const TWIN_RELATIONSHIPS_TABLE = 'simulation_digital_twin_relationships';

    public function findForSimulation(
        string $enterpriseId,
        string $digitalTwinId,
        string $digitalTwinRevisionId,
        string $baselineId,
    ): ?SimulationEnterpriseState {
        $enterprise = DB::table('simulation_enterprises')->where('id', $enterpriseId)->first();
        $digitalTwin = DB::table(self::DIGITAL_TWINS_TABLE)
            ->where('id', $digitalTwinId)
            ->where('enterprise_id', $enterpriseId)
            ->first();
        $digitalTwinRevision = DB::table('simulation_digital_twin_revisions')
            ->where('id', $digitalTwinRevisionId)
            ->where('enterprise_id', $enterpriseId)
            ->where('digital_twin_id', $digitalTwinId)
            ->first();
        $baseline = DB::table('simulation_baselines')
            ->where('id', $baselineId)
            ->where('enterprise_id', $enterpriseId)
            ->where('digital_twin_id', $digitalTwinId)
            ->where('digital_twin_revision_id', $digitalTwinRevisionId)
            ->first();

        if ($enterprise === null || $digitalTwin === null || $digitalTwinRevision === null || $baseline === null) {
            return null;
        }

        return $this->snapshot($enterprise, $digitalTwin, $digitalTwinRevision, $baseline);
    }

    public function findPublishedBaselineForSimulation(
        string $enterpriseId,
        string $baselineId,
    ): ?SimulationEnterpriseState {
        $baseline = DB::table('simulation_baselines')
            ->where('id', $baselineId)
            ->where('enterprise_id', $enterpriseId)
            ->where('status', 'PUBLISHED')
            ->first();

        if ($baseline === null) {
            return null;
        }

        return $this->findForSimulation(
            $enterpriseId,
            (string) $baseline->digital_twin_id,
            (string) $baseline->digital_twin_revision_id,
            $baselineId,
        );
    }

    public function findPublishedBaselineTargetForSimulation(string $baselineId): ?SimulationEnterpriseState
    {
        $baseline = DB::table('simulation_baselines')
            ->where('id', $baselineId)
            ->where('status', 'PUBLISHED')
            ->first();

        if ($baseline === null) {
            return null;
        }

        return $this->findForSimulation(
            (string) $baseline->enterprise_id,
            (string) $baseline->digital_twin_id,
            (string) $baseline->digital_twin_revision_id,
            $baselineId,
        );
    }

    /** @return array<string, mixed>|null */
    public function findPublishedDeviceTemplateRevisionForSimulation(string $revisionId): ?array
    {
        $revision = DB::table(self::DEVICE_REVISIONS_TABLE.' as revision')
            ->join(self::DEVICE_TEMPLATES_TABLE.' as template', 'template.id', '=', 'revision.device_template_id')
            ->where('revision.id', $revisionId)
            ->where('revision.status', 'PUBLISHED')
            ->first([
                'revision.*',
                'template.template_key',
                'template.device_type',
                'template.name_ar',
            ]);

        return $revision === null ? null : $this->deviceTemplateRevisionArray($revision);
    }

    /** @return list<array<string,mixed>> */
    public function listForSimulationWorkspace(): array
    {
        return DB::table('simulation_enterprises')
            ->orderBy('name_ar')
            ->get()
            ->map(function (stdClass $enterprise): array {
                $entities = DB::table(self::ENTITIES_TABLE)
                    ->where('enterprise_id', $enterprise->id)
                    ->orderBy('entity_key')
                    ->get()
                    ->map(function (stdClass $entity): array {
                        $revisions = DB::table('simulation_enterprise_entity_revisions')
                            ->where('enterprise_entity_id', $entity->id)
                            ->where('status', 'PUBLISHED')
                            ->orderByDesc('revision')
                            ->get()
                            ->map(fn (stdClass $rev): array => $this->entityRevisionArray($rev))
                            ->all();
                        return $this->entityArray($entity) + ['revisions' => $revisions];
                    })
                    ->all();
                $relationships = DB::table(self::ENTERPRISE_RELATIONSHIPS_TABLE)
                    ->where('enterprise_id', $enterprise->id)
                    ->orderBy('relationship_type')
                    ->orderBy('id')
                    ->get()
                    ->map(function (stdClass $relationship): array {
                        $revisions = DB::table('simulation_enterprise_relationship_revisions')
                            ->where('enterprise_relationship_id', $relationship->id)
                            ->where('status', 'PUBLISHED')
                            ->orderByDesc('revision')
                            ->get()
                            ->map(fn (stdClass $rev): array => $this->enterpriseRelationshipRevisionArray($rev))
                            ->all();
                        return $this->enterpriseRelationshipArray($relationship) + ['revisions' => $revisions];
                    })
                    ->all();
                $deviceTemplates = DB::table(self::DEVICE_TEMPLATES_TABLE)
                    ->where('enterprise_id', $enterprise->id)
                    ->orderBy('template_key')
                    ->get()
                    ->map(function (stdClass $template): array {
                        $revisions = DB::table(self::DEVICE_REVISIONS_TABLE)
                            ->where('device_template_id', $template->id)
                            ->orderByDesc('revision')
                            ->get()
                            ->map(fn (stdClass $revision): array => $this->deviceTemplateRevisionArray($revision))
                            ->all();

                        return [
                            'id' => (string) $template->id,
                            'enterprise_id' => (string) $template->enterprise_id,
                            'template_key' => (string) $template->template_key,
                            'device_type' => (string) $template->device_type,
                            'name_ar' => (string) $template->name_ar,
                            'name_en' => $template->name_en === null ? null : (string) $template->name_en,
                            'revisions' => $revisions,
                        ];
                    })
                    ->all();
                $twins = DB::table(self::DIGITAL_TWINS_TABLE)
                    ->where('enterprise_id', $enterprise->id)
                    ->orderBy('name_ar')
                    ->get()
                    ->map(function (stdClass $twin): array {
                        $revisions = DB::table('simulation_digital_twin_revisions')
                            ->where('digital_twin_id', $twin->id)
                            ->orderByDesc('revision')
                            ->get()
                            ->map(function (stdClass $revision): array {
                                $baselines = DB::table('simulation_baselines')
                                    ->where('digital_twin_revision_id', $revision->id)
                                    ->where('status', 'PUBLISHED')
                                    ->orderByDesc('revision')
                                    ->get()
                                    ->map(fn (stdClass $baseline): array => $this->baselineArray($baseline))
                                    ->all();
                                $components = DB::table(self::TWIN_COMPONENTS_TABLE)
                                    ->where('digital_twin_revision_id', $revision->id)
                                    ->orderBy('component_key')
                                    ->get()
                                    ->map(fn (stdClass $component): array => $this->twinComponentArray($component))
                                    ->all();
                                $relationships = DB::table(self::TWIN_RELATIONSHIPS_TABLE)
                                    ->where('digital_twin_revision_id', $revision->id)
                                    ->orderBy('relationship_type')
                                    ->orderBy('id')
                                    ->get()
                                    ->map(fn (stdClass $relationship): array => $this->twinRelationshipArray($relationship))
                                    ->all();

                                return $this->revisionArray($revision) + [
                                    'baselines' => $baselines,
                                    'components' => $components,
                                    'relationships' => $relationships,
                                ];
                            })
                            ->all();

                        return $this->twinArray($twin) + ['revisions' => $revisions];
                    })
                    ->all();

                return $this->enterpriseArray($enterprise) + [
                    'entities' => $entities,
                    'relationships' => $relationships,
                    'device_templates' => $deviceTemplates,
                    'digital_twins' => $twins,
                ];
            })
            ->all();
    }

    private function snapshot(
        stdClass $enterprise,
        stdClass $digitalTwin,
        stdClass $digitalTwinRevision,
        stdClass $baseline,
    ): SimulationEnterpriseState {
        return new SimulationEnterpriseState(
            enterprise: $this->enterpriseArray($enterprise),
            digitalTwin: $this->twinArray($digitalTwin),
            digitalTwinRevision: $this->revisionArray($digitalTwinRevision),
            baseline: $this->baselineArray($baseline),
        );
    }

    /** @return array<string,mixed> */
    private function enterpriseArray(stdClass $enterprise): array
    {
        return [
            'id' => (string) $enterprise->id,
            'slug' => (string) $enterprise->slug,
            'name_ar' => (string) $enterprise->name_ar,
            'name_en' => $enterprise->name_en === null ? null : (string) $enterprise->name_en,
            'description_ar' => $enterprise->description_ar === null ? null : (string) $enterprise->description_ar,
            'definition' => $this->decodeJson($enterprise->definition),
            'provenance' => (string) $enterprise->provenance,
            'is_fixture' => (bool) $enterprise->is_fixture,
            'created_by' => $enterprise->created_by === null ? null : (string) $enterprise->created_by,
            'created_at' => (string) $enterprise->created_at,
            'updated_at' => (string) $enterprise->updated_at,
        ];
    }

    /** @return array<string,mixed> */
    private function twinArray(stdClass $digitalTwin): array
    {
        return [
            'id' => (string) $digitalTwin->id,
            'enterprise_id' => (string) $digitalTwin->enterprise_id,
            'slug' => (string) $digitalTwin->slug,
            'name_ar' => (string) $digitalTwin->name_ar,
            'name_en' => $digitalTwin->name_en === null ? null : (string) $digitalTwin->name_en,
            'provenance' => (string) $digitalTwin->provenance,
            'is_fixture' => (bool) $digitalTwin->is_fixture,
            'created_by' => $digitalTwin->created_by === null ? null : (string) $digitalTwin->created_by,
            'created_at' => (string) $digitalTwin->created_at,
            'updated_at' => (string) $digitalTwin->updated_at,
        ];
    }

    /** @return array<string,mixed> */
    private function revisionArray(stdClass $revision): array
    {
        return [
            'id' => (string) $revision->id,
            'enterprise_id' => (string) $revision->enterprise_id,
            'digital_twin_id' => (string) $revision->digital_twin_id,
            'revision' => (int) $revision->revision,
            'status' => (string) $revision->status,
            'based_on_revision_id' => $revision->based_on_revision_id === null ? null : (string) $revision->based_on_revision_id,
            'topology' => $this->decodeJson($revision->topology),
            'behavior_model' => $this->decodeJson($revision->behavior_model),
            'validation_report' => $this->decodeJson($revision->validation_report),
            'digest' => (string) $revision->digest,
            'validated_at' => $revision->validated_at === null ? null : (string) $revision->validated_at,
            'published_at' => $revision->published_at === null ? null : (string) $revision->published_at,
            'created_by' => $revision->created_by === null ? null : (string) $revision->created_by,
            'created_at' => (string) $revision->created_at,
            'updated_at' => (string) $revision->updated_at,
        ];
    }

    /** @return array<string, mixed> */
        /** @return array<string, mixed> */
    private function entityRevisionArray(stdClass $rev): array
    {
        return [
            'id' => (string) $rev->id,
            'enterprise_id' => (string) $rev->enterprise_id,
            'enterprise_entity_id' => (string) $rev->enterprise_entity_id,
            'revision' => (int) $rev->revision,
            'status' => (string) $rev->status,
            'based_on_revision_id' => $rev->based_on_revision_id === null ? null : (string) $rev->based_on_revision_id,
            'lifecycle_state' => (string) $rev->lifecycle_state,
            'properties' => $this->decodeJson($rev->properties),
            'digest' => (string) $rev->digest,
            'published_at' => $rev->published_at === null ? null : (string) $rev->published_at,
            'created_by' => $rev->created_by === null ? null : (string) $rev->created_by,
            'created_at' => (string) $rev->created_at,
            'updated_at' => (string) $rev->updated_at,
        ];
    }

    /** @return array<string, mixed> */
    private function enterpriseRelationshipRevisionArray(stdClass $rev): array
    {
        return [
            'id' => (string) $rev->id,
            'enterprise_id' => (string) $rev->enterprise_id,
            'enterprise_relationship_id' => (string) $rev->enterprise_relationship_id,
            'revision' => (int) $rev->revision,
            'status' => (string) $rev->status,
            'based_on_revision_id' => $rev->based_on_revision_id === null ? null : (string) $rev->based_on_revision_id,
            'properties' => $this->decodeJson($rev->properties),
            'digest' => (string) $rev->digest,
            'published_at' => $rev->published_at === null ? null : (string) $rev->published_at,
            'created_by' => $rev->created_by === null ? null : (string) $rev->created_by,
            'created_at' => (string) $rev->created_at,
            'updated_at' => (string) $rev->updated_at,
        ];
    }
    
private function entityArray(stdClass $entity): array
    {
        return [
            'id' => (string) $entity->id,
            'enterprise_id' => (string) $entity->enterprise_id,
            'entity_key' => (string) $entity->entity_key,
            'entity_type' => (string) $entity->entity_type,
            'name_ar' => (string) $entity->name_ar,
            'name_en' => $entity->name_en === null ? null : (string) $entity->name_en,
        ];    }

    /** @return array<string, mixed> */
    private function enterpriseRelationshipArray(stdClass $relationship): array
    {
        return [
            'id' => (string) $relationship->id,
            'enterprise_id' => (string) $relationship->enterprise_id,
            'source_entity_id' => (string) $relationship->source_entity_id,
            'target_entity_id' => (string) $relationship->target_entity_id,
            'relationship_type' => (string) $relationship->relationship_type,
        ];    }

    /** @return array<string, mixed> */
    private function deviceTemplateRevisionArray(stdClass $revision): array
    {
        return [
            'id' => (string) $revision->id,
            'enterprise_id' => (string) $revision->enterprise_id,
            'device_template_id' => (string) $revision->device_template_id,
            'template_key' => property_exists($revision, 'template_key') ? (string) $revision->template_key : null,
            'device_type' => property_exists($revision, 'device_type') ? (string) $revision->device_type : null,
            'name_ar' => property_exists($revision, 'name_ar') ? (string) $revision->name_ar : null,
            'based_on_revision_id' => $revision->based_on_revision_id === null ? null : (string) $revision->based_on_revision_id,
            'revision' => (int) $revision->revision,
            'status' => (string) $revision->status,
            'capabilities' => $this->decodeJson($revision->capabilities),
            'state_model' => $this->decodeJson($revision->state_model),
            'actions' => $this->decodeJson($revision->actions),
            'events' => $this->decodeJson($revision->events),
            'telemetry' => $this->decodeJson($revision->telemetry),
            'behavior_rules' => $this->decodeJson($revision->behavior_rules),
            'validation_hooks' => $this->decodeJson($revision->validation_hooks),
            'validation_report' => $this->decodeJson($revision->validation_report),
            'digest' => (string) $revision->digest,
            'validated_at' => $revision->validated_at === null ? null : (string) $revision->validated_at,
            'published_at' => $revision->published_at === null ? null : (string) $revision->published_at,
        ];
    }

    /** @return array<string, mixed> */
    private function twinComponentArray(stdClass $component): array
    {
        return [
            'id' => (string) $component->id,
            'component_key' => (string) $component->component_key,
            'ownership_scope' => (string) $component->ownership_scope,
            'enterprise_entity_id' => $component->enterprise_entity_id === null ? null : (string) $component->enterprise_entity_id,
            'enterprise_entity_revision_id' => ($component->enterprise_entity_revision_id ?? null) === null ? null : (string) $component->enterprise_entity_revision_id,
            'device_template_revision_id' => $component->device_template_revision_id === null ? null : (string) $component->device_template_revision_id,
            'name_ar' => (string) $component->name_ar,
            'simulation_definition' => $this->decodeJson($component->simulation_definition),
        ];
    }

    /** @return array<string, mixed> */
    private function twinRelationshipArray(stdClass $relationship): array
    {
        return [
            'id' => (string) $relationship->id,
            'source_component_id' => (string) $relationship->source_component_id,
            'target_component_id' => (string) $relationship->target_component_id,
            'enterprise_relationship_id' => ($relationship->enterprise_relationship_id ?? null) === null ? null : (string) $relationship->enterprise_relationship_id,
            'enterprise_relationship_revision_id' => ($relationship->enterprise_relationship_revision_id ?? null) === null ? null : (string) $relationship->enterprise_relationship_revision_id,
            'relationship_type' => (string) $relationship->relationship_type,
            'properties' => $this->decodeJson($relationship->properties),
        ];
    }

    /** @return array<string,mixed> */
    private function baselineArray(stdClass $baseline): array
    {
        return [
            'id' => (string) $baseline->id,
            'enterprise_id' => (string) $baseline->enterprise_id,
            'digital_twin_id' => (string) $baseline->digital_twin_id,
            'digital_twin_revision_id' => (string) $baseline->digital_twin_revision_id,
            'revision' => (int) $baseline->revision,
            'status' => (string) $baseline->status,
            'state' => $this->decodeJson($baseline->state),
            'digest' => (string) $baseline->digest,
            'published_at' => $baseline->published_at === null ? null : (string) $baseline->published_at,
            'created_by' => $baseline->created_by === null ? null : (string) $baseline->created_by,
            'created_at' => (string) $baseline->created_at,
            'updated_at' => (string) $baseline->updated_at,
        ];
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
}
