<?php

namespace App\Modules\Platform\Blobs;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class LocalBlobStore implements BlobStore
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function writeStream(
        $stream,
        ?string $mediaType = null,
        string $ownerModule = 'MOD-PLT',
        string $purpose = 'generic',
        ?string $ownerIdentifier = null,
        string $status = 'ready',
    ): StoredBlob {
        if (! is_resource($stream)) {
            throw new InvalidArgumentException('A readable stream is required.');
        }
        if (preg_match('/^MOD-[A-Z]{3}$/', $ownerModule) !== 1 || ! in_array($status, ['ready', 'quarantined'], true)) {
            throw new InvalidArgumentException('Blob ownership or status is invalid.');
        }

        [$temporary, $size, $digest] = $this->copyAndHash($stream);
        $key = trim((string) config('platform.blob_root', 'blobs'), '/').'/'.date('Y/m/d').'/'.Str::uuid7().'.blob';
        $stagingKey = '.staging/'.Str::uuid7().'.partial';
        $disk = $this->filesystems->disk((string) config('platform.blob_disk'));
        $record = null;

        try {
            rewind($temporary);
            if (! $disk->put($stagingKey, $temporary) || ! $disk->move($stagingKey, $key)) {
                throw new RuntimeException('Blob atomic finalization failed.');
            }

            $record = DB::transaction(fn (): BlobObject => BlobObject::query()->create([
                'storage_key' => $key,
                'size_bytes' => $size,
                'sha256' => $digest,
                'media_type' => $mediaType !== null ? mb_substr($mediaType, 0, 160) : null,
                'status' => $status,
                'owner_module' => $ownerModule,
                'purpose' => mb_substr($purpose, 0, 80),
                'owner_identifier' => $ownerIdentifier !== null ? mb_substr($ownerIdentifier, 0, 160) : null,
                'created_at' => now(),
            ]));
        } catch (Throwable $exception) {
            $disk->delete([$stagingKey, $key]);
            throw $exception;
        } finally {
            fclose($temporary);
        }

        return new StoredBlob($key, $size, $digest, (string) $record->id);
    }

    public function readStream(string $key)
    {
        $this->guardKey($key);
        $stream = $this->filesystems->disk((string) config('platform.blob_disk'))->readStream($key);
        if (! is_resource($stream)) {
            throw new RuntimeException('Blob not found.');
        }

        return $stream;
    }

    public function restoreStream(string $key, $stream, string $expectedSha256, int $expectedBytes): void
    {
        $this->guardKey($key);
        if (! is_resource($stream) || preg_match('/^[0-9a-f]{64}$/', $expectedSha256) !== 1 || $expectedBytes < 0) {
            throw new InvalidArgumentException('Restore blob parameters are invalid.');
        }

        [$temporary, $size, $digest] = $this->copyAndHash($stream);
        if ($size !== $expectedBytes || ! hash_equals($expectedSha256, $digest)) {
            fclose($temporary);
            throw new RuntimeException('Restore blob digest or size mismatch.');
        }

        $disk = $this->filesystems->disk((string) config('platform.blob_disk'));
        $stagingKey = '.staging/'.Str::uuid7().'.restore';
        try {
            rewind($temporary);
            if (! $disk->put($stagingKey, $temporary) || ! $disk->move($stagingKey, $key)) {
                throw new RuntimeException('Restore blob atomic finalization failed.');
            }
        } finally {
            $disk->delete($stagingKey);
            fclose($temporary);
        }
    }

    public function verify(string $key, string $expectedSha256, int $expectedBytes): bool
    {
        try {
            $stream = $this->readStream($key);
            $context = hash_init('sha256');
            $bytes = 0;
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    return false;
                }
                $bytes += strlen($chunk);
                hash_update($context, $chunk);
            }
            fclose($stream);

            return $bytes === $expectedBytes && hash_equals($expectedSha256, hash_final($context));
        } catch (Throwable) {
            return false;
        }
    }

    public function delete(string $key): void
    {
        $this->guardKey($key);
        $disk = $this->filesystems->disk((string) config('platform.blob_disk'));
        if ($disk->exists($key) && ! $disk->delete($key)) {
            throw new RuntimeException('Blob deletion failed.');
        }
        BlobObject::query()->where('storage_key', $key)->update(['status' => 'deleted']);
    }

    /**
     * @param  resource  $stream
     * @return array{0:resource,1:int,2:string}
     */
    private function copyAndHash($stream): array
    {
        $temporary = tmpfile();
        if ($temporary === false) {
            throw new RuntimeException('Unable to create temporary blob stream.');
        }
        $context = hash_init('sha256');
        $size = 0;
        while (! feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false || fwrite($temporary, $chunk) === false) {
                fclose($temporary);
                throw new RuntimeException('Unable to copy blob stream.');
            }
            $size += strlen($chunk);
            hash_update($context, $chunk);
        }

        return [$temporary, $size, hash_final($context)];
    }

    private function guardKey(string $key): void
    {
        $root = trim((string) config('platform.blob_root', 'blobs'), '/');
        if ($key === '' || str_contains($key, '..') || str_contains($key, '\\') || str_starts_with($key, '/') || preg_match('/^[A-Za-z]:/', $key) || ! str_starts_with($key, $root.'/')) {
            throw new InvalidArgumentException('Unsafe blob storage key.');
        }
    }
}
