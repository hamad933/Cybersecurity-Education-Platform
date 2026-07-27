<?php

namespace App\Modules\Platform\Release;

use App\Modules\Platform\Audit\AuditChainVerifier;
use App\Modules\Platform\Backup\BackupManifest;
use App\Modules\Platform\Blobs\BlobObject;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Packages\PortablePackageRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ReleaseReadiness
{
    public function __construct(private readonly AuditChainVerifier $audit, private readonly BlobStore $blobs) {}

    /** @return array{ready:bool,checks:array<string,array{status:string,detail:string}>} */
    public function evaluate(): array
    {
        $checks = [];
        $checks['environment'] = $this->check(
            config('platform.auth_bypass') === false && in_array(config('platform.profile'), ['local', 'release', 'test'], true),
            'Authentication bypass is disabled and the profile is recognized.',
            'Authentication bypass or profile configuration is unsafe.',
        );
        try {
            DB::select('select 1');
            $checks['database'] = ['status' => 'PASS', 'detail' => 'PostgreSQL connection succeeded.'];
        } catch (Throwable $exception) {
            $checks['database'] = ['status' => 'FAIL', 'detail' => 'Database connection failed safely.'];
        }
        $checks['schema'] = $this->check(
            Schema::hasTable('portable_packages') && Schema::hasTable('prompt_packages') && Schema::hasTable('backup_manifests'),
            'Task-010 release schema is present.',
            'Task-010 release schema is incomplete.',
        );
        $chain = Schema::hasColumn('audit_records', 'record_hash') ? $this->audit->verify() : ['valid' => false, 'count' => 0];
        $checks['audit_chain'] = $this->check((bool) $chain['valid'], "Audit chain verified across {$chain['count']} records.", 'Audit chain verification failed.');
        $blobFailures = [];
        if (Schema::hasColumn('blob_objects', 'owner_module')) {
            foreach (BlobObject::query()->where('status', 'ready')->orderByDesc('created_at')->limit(100)->get() as $blob) {
                if (! $this->blobs->verify($blob->storage_key, $blob->sha256, (int) $blob->size_bytes)) {
                    $blobFailures[] = (string) $blob->id;
                }
            }
        }
        $checks['blob_integrity'] = $this->check($blobFailures === [], 'Bounded ready-blob integrity sample passed.', 'One or more ready blobs failed integrity verification.');
        $checks['manual_ai_only'] = $this->check(
            ! config('platform.ai_network_provider_enabled', false),
            'No network AI provider is enabled.',
            'A network AI provider is unexpectedly enabled.',
        );
        $checks['backup'] = $this->check(
            Schema::hasTable('backup_manifests') && BackupManifest::query()->where('status', 'verified')->exists(),
            'At least one verified V1 backup exists.',
            'No verified V1 backup exists yet.',
            'WARN',
        );
        $checks['packages'] = $this->check(
            ! Schema::hasTable('portable_packages') || PortablePackageRecord::query()->where('status', 'rejected')->doesntExist(),
            'No rejected package is unresolved in the release catalog.',
            'Rejected package records require review.',
            'WARN',
        );
        $ready = collect($checks)->every(fn (array $check): bool => $check['status'] !== 'FAIL');

        return ['ready' => $ready, 'checks' => $checks];
    }

    /** @return array{status:string,detail:string} */
    private function check(bool $condition, string $pass, string $fail, string $failureStatus = 'FAIL'): array
    {
        return ['status' => $condition ? 'PASS' : $failureStatus, 'detail' => $condition ? $pass : $fail];
    }
}
