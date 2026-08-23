<?php

namespace App\Modules\Enterprise\Application;

use Illuminate\Support\Facades\DB;
use stdClass;

final class DatabaseSimulationEnterpriseStateReader implements SimulationEnterpriseStateReader
{
    private const DIGITAL_TWINS_TABLE = 'simulation_digital_twins';

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

    /** @return list<array<string,mixed>> */
    public function listForSimulationWorkspace(): array
    {
        return DB::table('simulation_enterprises')
            ->orderBy('name_ar')
            ->get()
            ->map(function (stdClass $enterprise): array {
                $twins = DB::table(self::DIGITAL_TWINS_TABLE)
                    ->where('enterprise_id', $enterprise->id)
                    ->orderBy('name_ar')
                    ->get()
                    ->map(function (stdClass $twin): array {
                        $revisions = DB::table('simulation_digital_twin_revisions')
                            ->where('digital_twin_id', $twin->id)
                            ->where('status', 'PUBLISHED')
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

                                return $this->revisionArray($revision) + ['baselines' => $baselines];
                            })
                            ->all();

                        return $this->twinArray($twin) + ['revisions' => $revisions];
                    })
                    ->all();

                return $this->enterpriseArray($enterprise) + ['digital_twins' => $twins];
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
            'topology' => $this->decodeJson($revision->topology),
            'behavior_model' => $this->decodeJson($revision->behavior_model),
            'digest' => (string) $revision->digest,
            'published_at' => $revision->published_at === null ? null : (string) $revision->published_at,
            'created_by' => $revision->created_by === null ? null : (string) $revision->created_by,
            'created_at' => (string) $revision->created_at,
            'updated_at' => (string) $revision->updated_at,
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
