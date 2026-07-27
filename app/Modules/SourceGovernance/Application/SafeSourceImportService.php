<?php

namespace App\Modules\SourceGovernance\Application;

use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\SourceGovernance\Models\SourceImport;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class SafeSourceImportService
{
    /** @var array<string,list<string>> */
    private const ALLOWED = [
        'txt' => ['text/plain'],
        'md' => ['text/plain', 'text/markdown'],
        'json' => ['application/json', 'text/plain'],
        'pdf' => ['application/pdf'],
    ];

    public function __construct(private readonly BlobStore $blobs, private readonly AuditWriter $audit) {}

    public function import(UploadedFile $file, string $actorId): SourceImport
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Uploaded source is invalid.');
        }
        $name = mb_substr($file->getClientOriginalName(), 0, 300);
        $extension = mb_strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (! array_key_exists($extension, self::ALLOWED)) {
            throw new InvalidArgumentException('Source extension is not allowed.');
        }
        $max = (int) config('platform.source_import_max_bytes', 10_485_760);
        $size = (int) $file->getSize();
        if ($size <= 0 || $size > $max) {
            throw new InvalidArgumentException('Source size is outside the allowed bounds.');
        }
        $path = $file->getRealPath();
        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('Uploaded source is unavailable.');
        }
        $bytes = file_get_contents($path);
        if (! is_string($bytes) || strlen($bytes) !== $size) {
            throw new RuntimeException('Uploaded source could not be read completely.');
        }
        $mediaType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'application/octet-stream';
        $digest = hash('sha256', $bytes);
        $valid = in_array($mediaType, self::ALLOWED[$extension], true) && $this->signatureValid($extension, $bytes);

        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new RuntimeException('Unable to stage source import.');
        }
        fwrite($stream, $bytes);
        rewind($stream);
        try {
            $blob = $this->blobs->writeStream($stream, $mediaType, 'MOD-SRC', 'source-import', $actorId, $valid ? 'ready' : 'quarantined');
        } finally {
            fclose($stream);
        }
        if ($blob->id === null) {
            throw new RuntimeException('Source blob was not registered.');
        }

        return DB::transaction(function () use ($actorId, $name, $extension, $size, $mediaType, $digest, $valid, $blob): SourceImport {
            $source = null;
            if ($valid) {
                $source = SourceRecord::query()->create([
                    'authority_class' => 'Unresolved Gap',
                    'title' => $name,
                    'relative_path' => $blob->key,
                    'sha256' => $digest,
                    'review_status' => 'unreviewed',
                    'metadata' => ['origin' => 'manual_import', 'media_type' => $mediaType, 'size_bytes' => $size],
                ]);
            }
            $import = SourceImport::query()->create([
                'actor_id' => $actorId,
                'blob_object_id' => $blob->id,
                'source_record_id' => $source?->id,
                'original_name' => $name,
                'detected_media_type' => $mediaType,
                'extension' => $extension,
                'size_bytes' => $size,
                'sha256' => $digest,
                'status' => $valid ? 'accepted' : 'rejected',
                'rejection_code' => $valid ? null : 'TYPE_OR_SIGNATURE_MISMATCH',
                'reviewed_at' => $valid ? now() : null,
            ]);
            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => $valid ? 'source.import.accepted' : 'source.import.rejected',
                'target_type' => 'source_import',
                'target_identifier' => (string) $import->id,
                'correlation_id' => (string) $import->id,
                'outcome' => $valid ? 'success' : 'denied',
                'safe_metadata' => ['extension' => $extension, 'media_type' => $mediaType, 'size_bytes' => $size, 'sha256' => $digest],
            ]);

            return $import;
        });
    }

    private function signatureValid(string $extension, string $bytes): bool
    {
        return match ($extension) {
            'pdf' => str_starts_with($bytes, '%PDF-') && str_contains(substr($bytes, -2048), '%%EOF'),
            'json' => ! str_contains(substr($bytes, 0, 8192), "\0") && $this->validJson($bytes),
            'txt', 'md' => ! str_contains(substr($bytes, 0, 8192), "\0") && mb_check_encoding($bytes, 'UTF-8'),
            default => false,
        };
    }

    private function validJson(string $bytes): bool
    {
        try {
            $value = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);

            return is_array($value);
        } catch (Throwable) {
            return false;
        }
    }
}
