<?php

namespace App\Modules\Simulator\Application;

use LogicException;

final class IdempotencyConflict extends LogicException {}
