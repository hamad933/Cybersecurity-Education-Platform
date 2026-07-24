<?php

namespace App\Modules\Platform\Blobs;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class LocalBlobStore implements BlobStore
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function writeStream($stream): StoredBlob
    {
        if (! is_resource($stream)) {
            throw new InvalidArgumentException('A readable stream is required.');
        }
        $temporary = tmpfile();
        if ($temporary === false) {
            throw new RuntimeException('Unable to create temporary blob stream.');
        }
        $context = hash_init('sha256');
        $size = 0;
        while (! feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) {
                throw new RuntimeException('Unable to read input stream.');
            }
            $size += strlen($chunk);
            hash_update($context, $chunk);
            fwrite($temporary, $chunk);
        }
        $digest = hash_final($context);
        $key = date('Y/m/d').'/'.Str::uuid7().'.blob';
        $stagingKey = '.staging/'.Str::uuid7().'.partial';
        rewind($temporary);
        $disk = $this->filesystems->disk(config('platform.blob_disk'));
        if (! $disk->put($stagingKey, $temporary) || ! $disk->move($stagingKey, $key)) {
            $disk->delete($stagingKey);
            throw new RuntimeException('Blob atomic finalization failed.');
        }
        fclose($temporary);

        return new StoredBlob($key, $size, $digest);
    }

    public function readStream(string $key)
    {
        $this->guardKey($key);
        $stream = $this->filesystems->disk(config('platform.blob_disk'))->readStream($key);
        if ($stream === null) {
            throw new RuntimeException('Blob not found.');
        }

        return $stream;
    }

    public function delete(string $key): void
    {
        $this->guardKey($key);
        if (! $this->filesystems->disk(config('platform.blob_disk'))->delete($key)) {
            throw new RuntimeException('Blob deletion failed.');
        }
    }

    private function guardKey(string $key): void
    {
        if ($key === '' || str_contains($key, '..') || str_contains($key, '\\') || str_starts_with($key, '/') || preg_match('/^[A-Za-z]:/', $key)) {
            throw new InvalidArgumentException('Unsafe blob storage key.');
        }
    }
}
