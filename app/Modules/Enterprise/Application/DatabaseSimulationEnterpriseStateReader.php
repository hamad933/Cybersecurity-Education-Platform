<?php

namespace App\Modules\Enterprise\Application;

use Illuminate\Support\Facades\DB;
use stdClass;

final class DatabaseSimulationEnterpriseStateReader implements SimulationEnterpriseStateReader
{
    public function findForSimulation(
        string $enterpriseId,
        string $digitalTwinRevisionId,
        string $baselineId,
    ): ?SimulationEnterpriseState {
        $enterprise = DB::table('simulation_enterprises')
            ->where('id', $enterpriseId)
            ->first();
        $digitalTwinRevision = DB::table('simulation_digital_twin_revisions')
            ->where('id', $digitalTwinRevisionId)
            ->where('enterprise_id', $enterpriseId)
            ->first();
        $baseline = DB::table('simulation_baselines')
            ->where('id', $baselineId)
            ->where('enterprise_id', $enterpriseId)
            ->where('digital_twin_revision_id', $digitalTwinRevisionId)
            ->first();

        if ($enterprise === null || $digitalTwinRevision === null || $baseline === null) {
            return null;
        }

        return $this->snapshot($enterprise, $digitalTwinRevision, $baseline);
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
            (string) $baseline->digital_twin_revision_id,
            $baselineId,
        );
    }

    /** @return list<SimulationEnterpriseState> */
    public function listForSimulationWorkspace(): array
    {
        return DB::table('simulation_enterprises')
            ->orderBy('name_ar')
            ->get()
            ->map(function (stdClass $enterprise): SimulationEnterpriseState {
                $digitalTwinRevision = DB::table('simulation_digital_twin_revisions')
                    ->where('enterprise_id', $enterprise->id)
                    ->where('status', 'PUBLISHED')
                    ->orderByDesc('revision')
                    ->first();
                $baseline = DB::table('simulation_baselines')
                    ->where('enterprise_id', $enterprise->id)
                    ->where('status', 'PUBLISHED')
                    ->orderByDesc('revision')
                    ->first();

                return $this->snapshot($enterprise, $digitalTwinRevision, $baseline);
            })
            ->all();
    }

    private function snapshot(
        stdClass $enterprise,
        ?stdClass $digitalTwinRevision,
        ?stdClass $baseline,
    ): SimulationEnterpriseState {
        return new SimulationEnterpriseState(
            enterprise: [
                'id' => (string) $enterprise->id,
                'slug' => (string) $enterprise->slug,
                'name_ar' => (string) $enterprise->name_ar,
                'name_en' => $enterprise->name_en === null ? null : (string) $enterprise->name_en,
                'description_ar' => $enterprise->description_ar === null ? null : (string) $enterprise->description_ar,
                'definition' => $this->decodeJson($enterprise->definition),
                'is_fixture' => (bool) $enterprise->is_fixture,
                'created_by' => $enterprise->created_by === null ? null : (string) $enterprise->created_by,
                'created_at' => (string) $enterprise->created_at,
                'updated_at' => (string) $enterprise->updated_at,
            ],
            digitalTwinRevision: $digitalTwinRevision === null ? [] : [
                'id' => (string) $digitalTwinRevision->id,
                'enterprise_id' => (string) $digitalTwinRevision->enterprise_id,
                'revision' => (int) $digitalTwinRevision->revision,
                'status' => (string) $digitalTwinRevision->status,
                'topology' => $this->decodeJson($digitalTwinRevision->topology),
                'behavior_model' => $this->decodeJson($digitalTwinRevision->behavior_model),
                'digest' => (string) $digitalTwinRevision->digest,
                'published_at' => $digitalTwinRevision->published_at === null ? null : (string) $digitalTwinRevision->published_at,
                'created_by' => $digitalTwinRevision->created_by === null ? null : (string) $digitalTwinRevision->created_by,
                'created_at' => (string) $digitalTwinRevision->created_at,
                'updated_at' => (string) $digitalTwinRevision->updated_at,
            ],
            baseline: $baseline === null ? [] : [
                'id' => (string) $baseline->id,
                'enterprise_id' => (string) $baseline->enterprise_id,
                'digital_twin_revision_id' => (string) $baseline->digital_twin_revision_id,
                'revision' => (int) $baseline->revision,
                'status' => (string) $baseline->status,
                'state' => $this->decodeJson($baseline->state),
                'digest' => (string) $baseline->digest,
                'published_at' => $baseline->published_at === null ? null : (string) $baseline->published_at,
                'created_by' => $baseline->created_by === null ? null : (string) $baseline->created_by,
                'created_at' => (string) $baseline->created_at,
                'updated_at' => (string) $baseline->updated_at,
            ],
        );
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
