<?php

namespace App\Modules\Evidence\IntakeReview\Domain;

enum CandidateEvidenceState: string
{
    case RECEIVED = 'RECEIVED';
    case DRAFT = 'DRAFT';
    case PREPARED = 'PREPARED';
    case SUBMITTED_FOR_INTAKE = 'SUBMITTED_FOR_INTAKE';
    case RETURNED_FOR_CONTEXT = 'RETURNED_FOR_CONTEXT';
    case ADMITTED = 'ADMITTED';
    case DECLINED = 'DECLINED';
    case WITHDRAWN = 'WITHDRAWN';

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::RECEIVED => [self::DRAFT, self::PREPARED, self::WITHDRAWN],
            self::DRAFT => [self::PREPARED, self::WITHDRAWN],
            self::PREPARED => [self::SUBMITTED_FOR_INTAKE, self::WITHDRAWN],
            self::SUBMITTED_FOR_INTAKE => [
                self::RETURNED_FOR_CONTEXT,
                self::ADMITTED,
                self::DECLINED,
                self::WITHDRAWN,
            ],
            self::RETURNED_FOR_CONTEXT => [self::PREPARED, self::DECLINED, self::WITHDRAWN],
            self::ADMITTED, self::DECLINED, self::WITHDRAWN => [],
        };
    }

    public function terminal(): bool
    {
        return $this->allowedNext() === [];
    }
}
