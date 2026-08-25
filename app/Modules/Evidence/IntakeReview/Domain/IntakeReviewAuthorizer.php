<?php

namespace App\Modules\Evidence\IntakeReview\Domain;

final class IntakeReviewAuthorizer
{
    public function assertSubjectActor(string $subjectActorId, string $actorId): void
    {
        if ($subjectActorId === '' || $actorId === '' || ! hash_equals($subjectActorId, $actorId)) {
            throw new IntakeReviewException('Actor is outside the governed Evidence subject boundary.');
        }
    }

    public function assertReviewer(string $reviewerId, string $actorId): void
    {
        if ($reviewerId === '' || $actorId === '' || ! hash_equals($reviewerId, $actorId)) {
            throw new IntakeReviewException('Actor is not the assigned Evidence reviewer.');
        }
    }
}
