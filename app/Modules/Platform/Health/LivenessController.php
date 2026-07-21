<?php

namespace App\Modules\Platform\Health;

use Illuminate\Http\JsonResponse;

class LivenessController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
