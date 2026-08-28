<?php

namespace App\Modules\Platform\Health;

use Illuminate\Http\JsonResponse;

class LivenessController
{
    public function __invoke(FoundationHealth $health): JsonResponse
    {
        $checks = $health->summaryChecks();
        $isOk = collect($checks)->every(fn ($status) => $status === 'ok');

        return response()->json(
            ['status' => $isOk ? 'ok' : 'degraded', 'checks' => $checks],
            $isOk ? 200 : 503
        );
    }
}
