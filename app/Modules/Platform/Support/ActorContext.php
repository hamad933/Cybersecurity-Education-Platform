<?php

namespace App\Modules\Platform\Support;

final readonly class ActorContext
{
    public function __construct(public ?string $identifier, public string $correlationId) {}
}
