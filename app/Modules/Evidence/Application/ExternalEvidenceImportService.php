<?php

namespace App\Modules\Evidence\Application;

use App\Modules\Evidence\Models\ImportedEvidenceRecord;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Packages\SafePackageService;
use App\Modules\Platform\Support\CanonicalJson;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ExternalEvidenceImportService
{
    public function __construct(private readonly SafePackageService $packages, private readonly AuditWriter $audit) {}

    /** @param resource $stream */
    public function import($stream, string $actorId): ImportedEvidenceRecord
    {
        $verified = $this->packages->verifyStream($stream, ['external-evidence']);
        $manifestActor = $verified->manifest['actor_id'] ?? null;
        if (! is_string($manifestActor) || ! hash_equals($actorId, $manifestActor)) {
            throw new InvalidArgumentException('Evidence package actor binding failed.');
        }
        $payload = json_decode($verified->files['evidence.json'] ?? '', true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Evidence payload is invalid.');
        }
        $origin = $payload['origin'] ?? null;
        if (! in_array($origin, ['REAL_LAB', 'MANUAL_ASSESSMENT', 'SOURCE_REVIEW'], true)) {
            throw new InvalidArgumentException('External evidence origin is not permitted.');
        }
        foreach (['capability_id', 'knowledge_unit_id'] as $required) {
            if (! is_string($payload[$required] ?? null) || mb_strlen($payload[$required]) > 80) {
                throw new InvalidArgumentException('Evidence identity is invalid.');
            }
        }
        if (! is_array($payload['claims'] ?? null) || ! is_array($payload['limitations'] ?? null)) {
            throw new InvalidArgumentException('Evidence claims and limitations must be structured.');
        }
        $mirror = $this->packages->create('external-evidence', 1, $actorId, (array) ($verified->manifest['scope'] ?? []), $verified->files, ownerModule: 'MOD-EVD');

        return DB::transaction(function () use ($actorId, $origin, $payload, $mirror): ImportedEvidenceRecord {
            $record = ImportedEvidenceRecord::query()->create([
                'actor_id' => $actorId,
                'portable_package_id' => $mirror['record']->id,
                'origin' => $origin,
                'capability_id' => $payload['capability_id'],
                'knowledge_unit_id' => $payload['knowledge_unit_id'],
                'status' => 'pending_review',
                'claims' => $payload['claims'],
                'limitations' => $payload['limitations'],
                'content_digest' => CanonicalJson::sha256($payload),
            ]);
            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => 'external_evidence.imported',
                'target_type' => 'imported_evidence',
                'target_identifier' => (string) $record->id,
                'correlation_id' => (string) $record->id,
                'outcome' => 'success',
                'safe_metadata' => ['origin' => $origin, 'package_digest' => $mirror['manifest']['package_digest']],
            ]);

            return $record;
        });
    }

    public function decide(string $recordId, string $actorId, string $decision): ImportedEvidenceRecord
    {
        if (! in_array($decision, ['accepted', 'rejected'], true)) {
            throw new InvalidArgumentException('Evidence review decision is invalid.');
        }

        return DB::transaction(function () use ($recordId, $actorId, $decision): ImportedEvidenceRecord {
            $record = ImportedEvidenceRecord::query()->lockForUpdate()->whereKey($recordId)->where('actor_id', $actorId)->firstOrFail();
            if ($record->status !== 'pending_review') {
                throw new LogicException('Evidence has already received a final decision.');
            }
            $record->forceFill(['status' => $decision, 'reviewed_by' => $actorId, 'reviewed_at' => now()])->save();
            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => 'external_evidence.decided',
                'target_type' => 'imported_evidence',
                'target_identifier' => (string) $record->id,
                'correlation_id' => (string) $record->id,
                'outcome' => 'success',
                'safe_metadata' => ['decision' => $decision],
            ]);

            return $record;
        });
    }
}
