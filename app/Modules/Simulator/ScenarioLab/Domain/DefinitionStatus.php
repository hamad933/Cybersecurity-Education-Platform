<?php

declare(strict_types=1);

namespace App\Modules\Simulator\ScenarioLab\Domain;

enum DefinitionStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
}
