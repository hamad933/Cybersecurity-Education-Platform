<?php

namespace App\Modules\Platform\Support;

use LogicException;

trait ImmutableWhenFinal
{
    protected static function bootImmutableWhenFinal(): void
    {
        static::updating(function (self $model): void {
            if ($model->wasFinalBeforeUpdate()) {
                throw new LogicException('Finalized records are immutable; create a new revision instead.');
            }
        });
    }

    abstract protected function wasFinalBeforeUpdate(): bool;
}
