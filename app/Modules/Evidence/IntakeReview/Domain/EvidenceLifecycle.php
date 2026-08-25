<?php

namespace App\Modules\Evidence\IntakeReview\Domain;

enum EvidenceLifecycle: string
{
    case ACTIVE = 'ACTIVE';
    case WITHDRAWN = 'WITHDRAWN';
    case SUPERSEDED = 'SUPERSEDED';
}
