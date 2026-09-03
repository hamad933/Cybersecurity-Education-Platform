<?php

declare(strict_types=1);

namespace App\Application\Today\Values;

use Illuminate\Support\Str;

class OrchestrationNode
{
    private function __construct(
        public readonly OrchestrationStatus $status,
        public readonly mixed $data,
        public readonly ?string $message = null,
        public readonly ?string $diagnosticId = null,
        public readonly ?string $observedAt = null,
        public readonly ?string $freshUntil = null,
    ) {
    }

    public static function available(mixed $data): self
    {
        if ($data === null || $data === []) {
            throw new \InvalidArgumentException('AVAILABLE state strictly rejects null or empty array data (which must use EMPTY instead).');
        }

        return new self(OrchestrationStatus::AVAILABLE, $data);
    }

    public static function empty(): self
    {
        return new self(OrchestrationStatus::EMPTY, null);
    }

    public static function emptyArray(): self
    {
        return new self(OrchestrationStatus::EMPTY, []);
    }

    public static function unavailable(): self
    {
        return new self(OrchestrationStatus::UNAVAILABLE, null);
    }

    public static function error(string $message, string $diagnosticId): self
    {
        return new self(OrchestrationStatus::ERROR, null, $message, $diagnosticId);
    }

    public static function stale(mixed $data, string $observedAt, string $freshUntil, ?string $message = null): self
    {
        return new self(OrchestrationStatus::STALE, $data, $message, null, $observedAt, $freshUntil);
    }

    public function toArray(): array
    {
        $array = [
            'status' => $this->status->value,
            'data' => is_object($this->data) && method_exists($this->data, 'toArray') ? $this->data->toArray() : $this->data,
        ];

        if ($this->message !== null) {
            $array['message'] = $this->message;
        }

        if ($this->diagnosticId !== null) {
            $array['diagnosticId'] = $this->diagnosticId;
        }

        if ($this->observedAt !== null) {
            $array['observedAt'] = $this->observedAt;
        }

        if ($this->freshUntil !== null) {
            $array['freshUntil'] = $this->freshUntil;
        }

        return $array;
    }
}
