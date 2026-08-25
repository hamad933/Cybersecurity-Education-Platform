<?php

namespace App\Modules\Evidence\IntakeReview\Domain;

enum ReviewDecisionOutcome: string
{
    case ACCEPT = 'ACCEPT';
    case ACCEPT_WITH_LIMITATIONS = 'ACCEPT_WITH_LIMITATIONS';
    case MORE_EVIDENCE_REQUIRED = 'MORE_EVIDENCE_REQUIRED';
    case REJECT = 'REJECT';
}
