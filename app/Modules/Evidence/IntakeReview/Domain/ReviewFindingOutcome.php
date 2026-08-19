<?php

namespace App\Modules\Evidence\IntakeReview\Domain;

enum ReviewFindingOutcome: string
{
    case SATISFIED = 'SATISFIED';
    case PARTIALLY_SATISFIED = 'PARTIALLY_SATISFIED';
    case NOT_SATISFIED = 'NOT_SATISFIED';
    case NOT_ASSESSABLE = 'NOT_ASSESSABLE';
}
