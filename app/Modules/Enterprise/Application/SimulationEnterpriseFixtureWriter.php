<?php

namespace App\Modules\Enterprise\Application;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;

final class SimulationEnterpriseFixtureWriter
{
    public function hasFixture(string $slug): bool
    {
        return DB::table('simulation_enterprises')
            ->where('slug', $slug)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public function createEnterprise(
        string $slug,
        string $nameAr,
        array $definition,
        ?string $actorId = null,
    ): array {
        $id = (string) Str::uuid7();
        $now = now();

        DB::table('simulation_enterprises')->insert([
            'id' => $id,
            'slug' => $slug,
            'name_ar' => $nameAr,
            'name_en' => null,
            'description_ar' => null,
            'definition' => $this->json($definition),
            'is_fixture' => true,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_enterprises', $id);
    }

    /**
     * @param  array<string, mixed>  $topology
     * @param  array<string, mixed>  $behaviorModel
     * @return array<string, mixed>
     */
    public function publishDigitalTwinRevision(
        string $enterpriseId,
        array $topology,
        array $behaviorModel,
        ?string $actorId = null,
    ): array {
        $this->requireRow('simulation_enterprises', $enterpriseId);
        $revision = (int) DB::table('simulation_digital_twin_revisions')
            ->where('enterprise_id', $enterpriseId)
            ->max('revision') + 1;
        $id = (string) Str::uuid7();
        $now = now();
        $digest = $this->digest([
            'topology' => $topology,
            'behavior_model' => $behaviorModel,
        ]);

        DB::table('simulation_digital_twin_revisions')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'revision' => $revision,
            'status' => 'PUBLISHED',
            'topology' => $this->json($topology),
            'behavior_model' => $this->json($behaviorModel),
            'digest' => $digest,
            'published_at' => $now,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_digital_twin_revisions', $id);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function publishBaseline(
        string $enterpriseId,
        string $digitalTwinRevisionId,
        array $state,
        ?string $actorId = null,
    ): array {
        $digitalTwinRevision = $this->requireRow(
            'simulation_digital_twin_revisions',
            $digitalTwinRevisionId,
        );

        if (
            (string) $digitalTwinRevision->enterprise_id !== $enterpriseId
            || (string) $digitalTwinRevision->status !== 'PUBLISHED'
        ) {
            throw new LogicException('Baseline must pin a published Digital Twin Revision from the same Enterprise.');
        }

        $revision = (int) DB::table('simulation_baselines')
            ->where('enterprise_id', $enterpriseId)
            ->max('revision') + 1;
        $id = (string) Str::uuid7();
        $now = now();

        DB::table('simulation_baselines')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'digital_twin_revision_id' => $digitalTwinRevisionId,
            'revision' => $revision,
            'status' => 'PUBLISHED',
            'state' => $this->json($state),
            'digest' => $this->digest($state),
            'published_at' => $now,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_baselines', $id);
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
            throw new DomainException("Missing required Enterprise fixture record in {$table}.");
        }

        return $row;
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function digest(mixed $value): string
    {
        return hash(
            'sha256',
            json_encode(
                $this->canonicalize($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        );
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
