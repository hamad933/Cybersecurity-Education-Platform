<?php

declare(strict_types=1);

namespace App\Application\Today\Values;

enum OrchestrationStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case EMPTY = 'EMPTY';
    case UNAVAILABLE = 'UNAVAILABLE';
    case ERROR = 'ERROR';
    case STALE = 'STALE';
}
