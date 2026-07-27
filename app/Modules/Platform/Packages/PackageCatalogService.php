<?php

namespace App\Modules\Platform\Packages;

use App\Modules\Platform\Blobs\BlobObject;
use App\Modules\Platform\Blobs\BlobStore;
use RuntimeException;

final class PackageCatalogService
{
    public function __construct(private readonly BlobStore $blobs) {}

    /** @return array{stream:resource,name:string,media_type:string} */
    public function download(string $packageId, string $actorId): array
    {
        $package = PortablePackageRecord::query()->whereKey($packageId)->where('actor_id', $actorId)->firstOrFail();
        $blob = BlobObject::query()->findOrFail($package->blob_object_id);
        if (! $this->blobs->verify($blob->storage_key, $blob->sha256, (int) $blob->size_bytes)) {
            throw new RuntimeException('Package blob integrity verification failed.');
        }

        return [
            'stream' => $this->blobs->readStream($blob->storage_key),
            'name' => $package->package_type.'-'.$package->id.'.zip',
            'media_type' => 'application/zip',
        ];
    }
}
