<?php

namespace App\Modules\Platform\Backup;

use App\Modules\Platform\Audit\AuditChainVerifier;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Blobs\BlobObject;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Packages\PortablePackage;
use App\Modules\Platform\Packages\SafePackageService;
use App\Modules\Platform\Support\CanonicalJson;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class BackupService
{
    private const EXCLUDED_TABLES = [
        'migrations', 'application_sessions', 'password_reset_tokens', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs',
    ];

    public function __construct(
        private readonly SafePackageService $packages,
        private readonly BlobStore $blobs,
        private readonly AuditChainVerifier $auditChain,
        private readonly AuditWriter $audit,
    ) {}

    /** @return array{manifest:BackupManifest,package_id:string,blob_key:string} */
    public function create(string $actorId): array
    {
        $chain = $this->auditChain->verify();
        if (! $chain['valid']) {
            throw new RuntimeException('Backup refused because the audit chain is invalid.');
        }
        $tables = $this->tableSnapshot();
        $files = ['database.json' => CanonicalJson::encode(['schema_version' => 1, 'tables' => $tables])."\n"];
        $blobInventory = [];
        foreach (BlobObject::query()->whereIn('status', ['ready', 'quarantined'])->orderBy('id')->get() as $blob) {
            if (! $this->blobs->verify($blob->storage_key, $blob->sha256, (int) $blob->size_bytes)) {
                throw new RuntimeException("Blob integrity failed before backup: {$blob->id}");
            }
            $stream = $this->blobs->readStream($blob->storage_key);
            try {
                $bytes = stream_get_contents($stream);
            } finally {
                fclose($stream);
            }
            if (! is_string($bytes)) {
                throw new RuntimeException('Blob could not be read for backup.');
            }
            $path = 'blobs/'.strtolower((string) $blob->id).'.blob';
            $files[$path] = $bytes;
            $blobInventory[] = [
                'id' => (string) $blob->id,
                'path' => $path,
                'storage_key' => $blob->storage_key,
                'size_bytes' => (int) $blob->size_bytes,
                'sha256' => $blob->sha256,
                'status' => $blob->status,
            ];
        }
        $counts = [];
        foreach ($tables as $name => $rows) {
            $counts[$name] = count($rows);
        }
        $scope = [
            'backup_format' => 'logical-v1',
            'actor_id' => $actorId,
            'created_at' => now()->utc()->toIso8601String(),
            'database_driver' => DB::connection()->getDriverName(),
            'table_counts' => $counts,
            'blob_inventory' => $blobInventory,
            'audit_chain_count' => $chain['count'],
            'encryption' => 'NOT_IMPLEMENTED_LOCAL_V1',
        ];
        $package = $this->packages->create('platform-backup', 1, $actorId, $scope, $files, ownerModule: 'MOD-PLT');
        $manifest = BackupManifest::query()->create([
            'actor_id' => $actorId,
            'portable_package_id' => $package['record']->id,
            'status' => 'verified',
            'database_driver' => DB::connection()->getDriverName(),
            'table_counts' => $counts,
            'blob_inventory' => $blobInventory,
            'content_digest' => CanonicalJson::sha256(['tables' => $tables, 'blobs' => $blobInventory]),
            'created_at' => now(),
        ]);
        $this->audit->append([
            'actor_identifier' => $actorId,
            'action' => 'backup.created',
            'target_type' => 'backup_manifest',
            'target_identifier' => (string) $manifest->id,
            'correlation_id' => (string) $manifest->id,
            'outcome' => 'success',
            'safe_metadata' => ['package_digest' => $package['manifest']['package_digest'], 'table_count' => count($counts), 'blob_count' => count($blobInventory)],
        ]);

        return ['manifest' => $manifest, 'package_id' => (string) $package['record']->id, 'blob_key' => $package['blob_key']];
    }

    /** @param resource $stream */
    public function inspect($stream): PortablePackage
    {
        $package = $this->packages->verifyStream($stream, ['platform-backup']);
        $this->validatePayload($package);

        return $package;
    }

    /** @param resource $stream */
    public function stage($stream, string $actorId): RestoreRun
    {
        $verified = $this->inspect($stream);
        if (($verified->manifest['actor_id'] ?? null) !== $actorId) {
            throw new InvalidArgumentException('Backup actor binding does not match the current owner.');
        }
        $mirror = $this->packages->create('platform-backup', 1, $actorId, (array) $verified->manifest['scope'], $verified->files, ownerModule: 'MOD-PLT');
        $database = json_decode($verified->files['database.json'], true, 128, JSON_THROW_ON_ERROR);
        $tables = $database['tables'];
        $counts = array_map('count', $tables);
        $inventory = (array) ($verified->manifest['scope']['blob_inventory'] ?? []);
        $manifest = BackupManifest::query()->create([
            'actor_id' => $actorId,
            'portable_package_id' => $mirror['record']->id,
            'status' => 'verified',
            'database_driver' => (string) ($verified->manifest['scope']['database_driver'] ?? 'pgsql'),
            'table_counts' => $counts,
            'blob_inventory' => $inventory,
            'content_digest' => CanonicalJson::sha256(['tables' => $tables, 'blobs' => $inventory]),
            'created_at' => now(),
        ]);
        $run = RestoreRun::query()->create([
            'actor_id' => $actorId,
            'backup_manifest_id' => $manifest->id,
            'target_database' => (string) config('database.connections.pgsql.database'),
            'status' => 'staged',
            'verification' => ['package_sha256' => $verified->archiveSha256, 'tables' => $counts, 'activation_allowed' => false],
            'started_at' => now(),
        ]);
        $this->audit->append([
            'actor_identifier' => $actorId,
            'action' => 'restore.staged',
            'target_type' => 'restore_run',
            'target_identifier' => (string) $run->id,
            'correlation_id' => (string) $run->id,
            'outcome' => 'success',
            'safe_metadata' => ['activation_allowed' => false, 'package_sha256' => $verified->archiveSha256],
        ]);

        return $run;
    }

    public function applyToIsolatedDatabase(PortablePackage $package, string $actorId): RestoreRun
    {
        if (($package->manifest['actor_id'] ?? null) !== $actorId) {
            throw new InvalidArgumentException('Backup actor binding does not match the restore actor.');
        }
        $databaseName = (string) DB::connection()->getDatabaseName();
        if (! str_ends_with($databaseName, '_restore_drill')) {
            throw new LogicException('Restore activation is restricted to an isolated _restore_drill database.');
        }
        $this->validatePayload($package);
        $database = json_decode($package->files['database.json'], true, 128, JSON_THROW_ON_ERROR);
        $tables = $database['tables'];
        $inventory = (array) ($package->manifest['scope']['blob_inventory'] ?? []);

        DB::transaction(function () use ($tables): void {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('SET LOCAL session_replication_role = replica');
            }
            foreach (array_reverse(array_keys($tables)) as $table) {
                DB::statement('TRUNCATE TABLE '.$this->quoteIdentifier($table).' CASCADE');
            }
            foreach ($tables as $table => $rows) {
                foreach (array_chunk($rows, 100) as $chunk) {
                    if ($chunk !== []) {
                        DB::table($table)->insert($chunk);
                    }
                }
            }
        }, 1);

        foreach ($inventory as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Backup blob inventory entry is invalid.');
            }
            $path = $item['path'] ?? null;
            if (! is_string($path) || ! isset($package->files[$path])) {
                throw new InvalidArgumentException('Backup blob payload is missing.');
            }
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, $package->files[$path]);
            rewind($stream);
            try {
                $this->blobs->restoreStream((string) $item['storage_key'], $stream, (string) $item['sha256'], (int) $item['size_bytes']);
            } finally {
                fclose($stream);
            }
        }

        $counts = array_map('count', $tables);
        // Verify the restored snapshot before recovery bookkeeping writes
        // new package, manifest, audit, and restore-run rows.
        $verification = $this->verifyRestoredCounts($counts, $inventory);

        $mirror = $this->packages->create('platform-backup', 1, $actorId, (array) $package->manifest['scope'], $package->files, ownerModule: 'MOD-PLT');
        $manifest = BackupManifest::query()->create([
            'actor_id' => $actorId,
            'portable_package_id' => $mirror['record']->id,
            'status' => 'verified',
            'database_driver' => DB::connection()->getDriverName(),
            'table_counts' => $counts,
            'blob_inventory' => $inventory,
            'content_digest' => CanonicalJson::sha256(['tables' => $tables, 'blobs' => $inventory]),
            'created_at' => now(),
        ]);
        $run = RestoreRun::query()->create([
            'actor_id' => $actorId,
            'backup_manifest_id' => $manifest->id,
            'target_database' => $databaseName,
            'status' => $verification['valid'] ? 'verified' : 'failed',
            'verification' => $verification,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $this->audit->append([
            'actor_identifier' => $actorId,
            'action' => 'restore.drill.completed',
            'target_type' => 'restore_run',
            'target_identifier' => (string) $run->id,
            'correlation_id' => (string) $run->id,
            'outcome' => $verification['valid'] ? 'success' : 'failure',
            'safe_metadata' => ['target_database' => $databaseName, 'valid' => $verification['valid']],
        ]);

        return $run;
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function tableSnapshot(): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException('V1 backup requires PostgreSQL.');
        }
        $names = array_map(
            fn (object $row): string => (string) $row->tablename,
            DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"),
        );
        $result = [];
        foreach ($names as $name) {
            if (in_array($name, self::EXCLUDED_TABLES, true)) {
                continue;
            }
            $rows = DB::select('SELECT * FROM '.$this->quoteIdentifier($name).' ORDER BY 1');
            $normalized = [];
            foreach ($rows as $row) {
                $values = (array) $row;
                unset($values['search_vector']);
                ksort($values, SORT_STRING);
                $normalized[] = $values;
            }
            $result[$name] = $normalized;
        }

        return $result;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (preg_match('/^[a-z][a-z0-9_]{0,62}$/', $identifier) !== 1) {
            throw new InvalidArgumentException('Unsafe database identifier.');
        }

        return '"'.$identifier.'"';
    }

    private function validatePayload(PortablePackage $package): void
    {
        $database = json_decode($package->files['database.json'] ?? '', true, 128, JSON_THROW_ON_ERROR);
        if (! is_array($database) || ($database['schema_version'] ?? null) !== 1 || ! is_array($database['tables'] ?? null)) {
            throw new InvalidArgumentException('Backup database payload is invalid.');
        }
        foreach ($database['tables'] as $table => $rows) {
            $this->quoteIdentifier((string) $table);
            if (in_array($table, self::EXCLUDED_TABLES, true) || ! is_array($rows) || ! array_is_list($rows)) {
                throw new InvalidArgumentException('Backup contains an excluded or malformed table.');
            }
        }
        foreach ((array) ($package->manifest['scope']['blob_inventory'] ?? []) as $item) {
            if (! is_array($item) || ! isset($item['path'], $item['storage_key'], $item['size_bytes'], $item['sha256'])) {
                throw new InvalidArgumentException('Backup blob inventory is invalid.');
            }
            if (! isset($package->files[$item['path']]) || strlen($package->files[$item['path']]) !== (int) $item['size_bytes'] || ! hash_equals((string) $item['sha256'], hash('sha256', $package->files[$item['path']]))) {
                throw new InvalidArgumentException('Backup blob integrity validation failed.');
            }
        }
    }

    /**
     * @param  array<string,int>  $counts
     * @param  list<array<string,mixed>>  $inventory
     * @return array{
     *     valid:bool,
     *     table_mismatches:array<string,array{expected:int,actual:int}>,
     *     blob_failures:list<string>,
     *     audit_chain:array{valid:bool,count:int,first_invalid_sequence:?int}
     * }
     */
    private function verifyRestoredCounts(array $counts, array $inventory): array
    {
        $mismatches = [];
        foreach ($counts as $table => $expected) {
            $actual = (int) (DB::selectOne('SELECT COUNT(*) AS aggregate FROM '.$this->quoteIdentifier($table))->aggregate ?? -1);
            if ($actual !== $expected) {
                $mismatches[$table] = ['expected' => $expected, 'actual' => $actual];
            }
        }
        $blobFailures = [];
        foreach ($inventory as $item) {
            if (! $this->blobs->verify((string) $item['storage_key'], (string) $item['sha256'], (int) $item['size_bytes'])) {
                $blobFailures[] = (string) ($item['id'] ?? $item['storage_key']);
            }
        }
        $chain = $this->auditChain->verify();

        return [
            'valid' => $mismatches === [] && $blobFailures === [] && $chain['valid'],
            'table_mismatches' => $mismatches,
            'blob_failures' => $blobFailures,
            'audit_chain' => $chain,
        ];
    }
}
