<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'owner' => fn () => $request->user()?->only(['id', 'display_name']),
            ],
            'environment' => [
                'name' => config('app.env'),
                'profile' => config('platform.profile'),
                'localOnly' => true,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
        ];
    }
}
