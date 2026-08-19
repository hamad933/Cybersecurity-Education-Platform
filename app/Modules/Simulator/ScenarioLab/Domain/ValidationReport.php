<?php

declare(strict_types=1);

namespace App\Modules\Simulator\ScenarioLab\Domain;

final readonly class ValidationReport
{
    /**
     * @param list<string> $errors
     * @param list<string> $warnings
     */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array{valid: bool, errors: list<string>, warnings: list<string>} */
    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
