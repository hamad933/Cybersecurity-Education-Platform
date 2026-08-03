<?php

namespace App\Modules\Platform\Packages;

use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Support\CanonicalJson;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use ZipArchive;

final class SafePackageService
{
    public function __construct(
        private readonly PackagePathGuard $paths,
        private readonly BlobStore $blobs,
        private readonly AuditWriter $audit,
    ) {}

    /**
     * @param  array<string,mixed>  $scope
     * @param  array<array-key,mixed>  $files
     * @return array{record:PortablePackageRecord,blob_key:string,manifest:array<string,mixed>}
     */
    public function create(
        string $packageType,
        int $schemaVersion,
        string $actorId,
        array $scope,
        array $files,
        ?PackageLimits $limits = null,
        string $ownerModule = 'MOD-PLT',
    ): array {
        $limits ??= new PackageLimits;
        $this->guardIdentity($packageType, $schemaVersion, $ownerModule);
        if ($files === [] || count($files) > $limits->maxFiles) {
            throw new InvalidArgumentException('Package file count is invalid.');
        }

        $normalized = [];
        $entries = [];
        $contents = [];
        $total = 0;
        foreach ($files as $path => $content) {
            if (! is_string($path) || ! is_string($content)) {
                throw new InvalidArgumentException('Package files must be string paths and bytes.');
            }
            $path = $this->paths->normalize($path, $limits);
            $lower = mb_strtolower($path);
            if ($path === 'manifest.json' || isset($normalized[$lower])) {
                throw new InvalidArgumentException('Reserved or duplicate package path rejected.');
            }
            $bytes = strlen($content);
            $total += $bytes;
            if ($bytes > $limits->maxFileBytes || $total > $limits->maxTotalBytes) {
                throw new InvalidArgumentException('Package size limit exceeded.');
            }
            $normalized[$lower] = $path;
            $contents[$path] = $content;
            $entries[$path] = ['path' => $path, 'bytes' => $bytes, 'sha256' => hash('sha256', $content)];
        }
        ksort($entries, SORT_STRING);

        $base = [
            'format' => 'cyber-platform-portable-package',
            'package_type' => $packageType,
            'schema_version' => $schemaVersion,
            'actor_id' => $actorId,
            'owner_module' => $ownerModule,
            'scope' => $scope,
            'files' => array_values($entries),
        ];
        $manifest = [...$base, 'package_digest' => CanonicalJson::sha256($base)];
        $manifestJson = CanonicalJson::encode($manifest)."\n";

        $temporary = tempnam(sys_get_temp_dir(), 'task010-package-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to allocate package archive.');
        }
        $blob = null;
        try {
            $zip = new ZipArchive;
            if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create package archive.');
            }
            $zip->addFromString('manifest.json', $manifestJson);
            foreach ($entries as $path => $_entry) {
                $zip->addFromString($path, $contents[$path]);
            }
            $zip->close();

            $stream = fopen($temporary, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Unable to open generated package.');
            }
            try {
                $blob = $this->blobs->writeStream($stream, 'application/zip', $ownerModule, $packageType, $actorId);
            } finally {
                fclose($stream);
            }
            if ($blob->id === null) {
                throw new RuntimeException('Package blob identifier was not recorded.');
            }

            $record = DB::transaction(function () use ($packageType, $schemaVersion, $ownerModule, $actorId, $scope, $manifest, $blob): PortablePackageRecord {
                $record = PortablePackageRecord::query()->create([
                    'package_type' => $packageType,
                    'schema_version' => $schemaVersion,
                    'owner_module' => $ownerModule,
                    'actor_id' => $actorId,
                    'scope' => $scope,
                    'manifest' => $manifest,
                    'package_digest' => $manifest['package_digest'],
                    'blob_object_id' => $blob->id,
                    'status' => 'exported',
                    'created_at' => now(),
                ]);
                $this->audit->append([
                    'actor_identifier' => $actorId,
                    'action' => 'portable_package.exported',
                    'target_type' => 'portable_package',
                    'target_identifier' => (string) $record->id,
                    'correlation_id' => (string) $record->id,
                    'outcome' => 'success',
                    'safe_metadata' => [
                        'package_type' => $packageType,
                        'schema_version' => $schemaVersion,
                        'package_digest' => $manifest['package_digest'],
                        'file_count' => count($manifest['files']),
                    ],
                ]);

                return $record;
            });

            return ['record' => $record, 'blob_key' => $blob->key, 'manifest' => $manifest];
        } catch (Throwable $exception) {
            if ($blob !== null) {
                try {
                    $this->blobs->delete($blob->key);
                } catch (Throwable) {
                }
            }
            throw $exception;
        } finally {
            @unlink($temporary);
        }
    }

    /**
     * @param  resource  $stream
     * @param  list<string>  $allowedTypes
     */
    public function verifyStream($stream, array $allowedTypes, ?PackageLimits $limits = null): PortablePackage
    {
        if (! is_resource($stream)) {
            throw new InvalidArgumentException('A readable package stream is required.');
        }
        $limits ??= new PackageLimits;
        $temporary = tempnam(sys_get_temp_dir(), 'task010-verify-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to allocate verification archive.');
        }
        $output = fopen($temporary, 'wb');
        if ($output === false) {
            @unlink($temporary);
            throw new RuntimeException('Unable to open verification archive.');
        }
        $hash = hash_init('sha256');
        $archiveBytes = 0;
        while (! feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) {
                fclose($output);
                @unlink($temporary);
                throw new RuntimeException('Unable to read package stream.');
            }
            $archiveBytes += strlen($chunk);
            if ($archiveBytes > $limits->maxTotalBytes) {
                fclose($output);
                @unlink($temporary);
                throw new InvalidArgumentException('Package archive exceeds limit.');
            }
            hash_update($hash, $chunk);
            fwrite($output, $chunk);
        }
        fclose($output);
        $archiveSha = hash_final($hash);

        try {
            $zip = new ZipArchive;
            if ($zip->open($temporary, ZipArchive::RDONLY) !== true) {
                throw new InvalidArgumentException('Package is not a valid ZIP archive.');
            }
            if ($zip->numFiles < 2 || $zip->numFiles > $limits->maxFiles + 1) {
                $zip->close();
                throw new InvalidArgumentException('Package file count is invalid.');
            }
            $seen = [];
            $contents = [];
            $uncompressed = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if ($stat === false) {
                    $zip->close();
                    throw new InvalidArgumentException('Unreadable package entry.');
                }
                $name = $this->paths->normalize((string) $stat['name'], $limits);
                $lower = mb_strtolower($name);
                if (isset($seen[$lower]) || str_ends_with($name, '/')) {
                    $zip->close();
                    throw new InvalidArgumentException('Duplicate or directory package entry rejected.');
                }
                $seen[$lower] = true;
                $size = (int) $stat['size'];
                $compressed = (int) $stat['comp_size'];
                $uncompressed += $size;
                if ($size > $limits->maxFileBytes || $uncompressed > $limits->maxTotalBytes || ($compressed > 0 && $size > $compressed * $limits->maxCompressionRatio)) {
                    $zip->close();
                    throw new InvalidArgumentException('Package expansion limit exceeded.');
                }
                $bytes = $zip->getFromIndex($index);
                if (! is_string($bytes) || strlen($bytes) !== $size) {
                    $zip->close();
                    throw new InvalidArgumentException('Package entry could not be read completely.');
                }
                $contents[$name] = $bytes;
            }
            $zip->close();

            if (! isset($contents['manifest.json'])) {
                throw new InvalidArgumentException('Package manifest is missing.');
            }
            $manifest = json_decode($contents['manifest.json'], true, 64, JSON_THROW_ON_ERROR);
            if (! is_array($manifest) || ($manifest['format'] ?? null) !== 'cyber-platform-portable-package') {
                throw new InvalidArgumentException('Package manifest format is invalid.');
            }
            $type = $manifest['package_type'] ?? null;
            $version = $manifest['schema_version'] ?? null;
            if (! is_string($type) || ! in_array($type, $allowedTypes, true) || ! is_int($version) || $version !== 1) {
                throw new InvalidArgumentException('Package type or schema version is not supported.');
            }
            $base = $manifest;
            $declaredDigest = $base['package_digest'] ?? null;
            unset($base['package_digest']);
            if (! is_string($declaredDigest) || ! hash_equals(CanonicalJson::sha256($base), $declaredDigest)) {
                throw new InvalidArgumentException('Package manifest digest mismatch.');
            }
            $declared = $manifest['files'] ?? null;
            if (! is_array($declared) || ! array_is_list($declared)) {
                throw new InvalidArgumentException('Package manifest file list is invalid.');
            }
            $files = [];
            $declaredNames = [];
            foreach ($declared as $entry) {
                if (! is_array($entry) || ! is_string($entry['path'] ?? null) || ! is_int($entry['bytes'] ?? null) || ! is_string($entry['sha256'] ?? null)) {
                    throw new InvalidArgumentException('Package manifest file entry is invalid.');
                }
                $name = $this->paths->normalize($entry['path'], $limits);
                if ($name === 'manifest.json' || isset($declaredNames[mb_strtolower($name)]) || ! isset($contents[$name])) {
                    throw new InvalidArgumentException('Package declared file set is missing or duplicated.');
                }
                $declaredNames[mb_strtolower($name)] = true;
                if (strlen($contents[$name]) !== $entry['bytes'] || ! hash_equals($entry['sha256'], hash('sha256', $contents[$name]))) {
                    throw new InvalidArgumentException('Package file size or digest mismatch.');
                }
                $files[$name] = $contents[$name];
            }
            if (count($files) + 1 !== count($contents)) {
                throw new InvalidArgumentException('Package contains undeclared extra files.');
            }

            return new PortablePackage($manifest, $files, $archiveSha, $archiveBytes);
        } finally {
            @unlink($temporary);
        }
    }

    private function guardIdentity(string $type, int $version, string $module): void
    {
        if (preg_match('/^[a-z0-9][a-z0-9.-]{2,79}$/', $type) !== 1 || $version !== 1 || preg_match('/^MOD-[A-Z]{3}$/', $module) !== 1) {
            throw new InvalidArgumentException('Package identity is invalid.');
        }
    }
}
