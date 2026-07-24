<?php

namespace App\Modules\Platform\Blobs;

interface BlobStore
{
    /** @param resource $stream */
    public function writeStream($stream): StoredBlob;

    /** @return resource */
    public function readStream(string $key);

    public function delete(string $key): void;
}
