<?php

namespace App\Modules\Evidence\IntakeReview\Domain;

enum ReviewStatus: string
{
    case UNREVIEWED = 'UNREVIEWED';
    case IN_REVIEW = 'IN_REVIEW';
    case REVIEWED = 'REVIEWED';
}
