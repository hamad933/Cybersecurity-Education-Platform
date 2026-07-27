<?php

namespace App\Modules\Enterprise\Application;

use App\Modules\Enterprise\Models\EnterpriseBaselineRevision;
use App\Modules\Enterprise\Models\ImprovementProposal;
use InvalidArgumentException;

final class EnterpriseBaselineService
{
    /** @return array{id:string,snapshot_digest:string} */
    public function publishedRevision(string $revisionId): array
    {
        $revision = EnterpriseBaselineRevision::query()
            ->whereKey($revisionId)
            ->where('state', 'published')
            ->firstOrFail();

        return ['id' => (string) $revision->id, 'snapshot_digest' => (string) $revision->snapshot_digest];
    }

    /**
     * Read-only VS-002 facts. Request context selects identities/resources but
     * cannot supply their role, owner, or existence facts.
     *
     * @param  array{actor_id?:mixed,resource_id?:mixed}  $requestContext
     * @return array<string,mixed>
     */
    public function webAuthorizationFacts(string $revisionId, array $requestContext): array
    {
        $revision = EnterpriseBaselineRevision::query()->whereKey($revisionId)->where('state', 'published')->firstOrFail();
        $snapshotValue = $revision->getAttribute('snapshot');
        $snapshot = is_array($snapshotValue) ? $snapshotValue : [];
        $actorsValue = $snapshot['actors'] ?? null;
        $resourcesValue = $snapshot['resources'] ?? null;
        $actors = is_array($actorsValue) ? $actorsValue : [];
        $resources = is_array($resourcesValue) ? $resourcesValue : [];
        $actorId = is_string($requestContext['actor_id'] ?? null) ? $requestContext['actor_id'] : '';
        $resourceId = is_string($requestContext['resource_id'] ?? null) ? $requestContext['resource_id'] : '';
        $actor = collect($actors)->first(fn (mixed $item): bool => is_array($item) && ($item['id'] ?? null) === $actorId);
        $resource = collect($resources)->first(fn (mixed $item): bool => is_array($item) && ($item['id'] ?? null) === $resourceId);

        return [
            'baseline_revision_id' => (string) $revision->id,
            'baseline_digest' => (string) $revision->snapshot_digest,
            'actor_exists' => is_array($actor),
            'actor_id' => $actorId,
            'server_role' => is_array($actor) && is_string($actor['server_role'] ?? null) ? $actor['server_role'] : null,
            'resource_exists' => is_array($resource),
            'resource_id' => $resourceId,
            'resource_owner_id' => is_array($resource) && is_string($resource['owner_id'] ?? null) ? $resource['owner_id'] : null,
            'approved_method' => 'GET',
            'approved_action' => 'case_file.read',
        ];
    }

    /**
     * @param  array<string, mixed>  $proposal
     * @return array<string,mixed>
     */
    public function proposeImprovement(string $baselineRevisionId, string $runId, array $proposal): array
    {
        if ($proposal === [] || strlen(json_encode($proposal, JSON_THROW_ON_ERROR)) > 4096) {
            throw new InvalidArgumentException('A bounded improvement proposal is required.');
        }

        $record = ImprovementProposal::query()->create([
            'enterprise_baseline_revision_id' => $baselineRevisionId,
            'scenario_run_id' => $runId,
            'proposal' => $proposal,
            'status' => 'proposed',
        ]);

        return $record->toArray();
    }
}
