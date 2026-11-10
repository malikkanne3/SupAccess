<?php

namespace App\Http\Middleware;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'service' => $user->service,
                    'initiales' => $user->initiales,
                ] : null,
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'non_lues' => fn () => $user ? $user->notifications()->whereNull('lu_at')->count() : 0,
            'en_attente' => fn () => $user && $user->role === User::GESTIONNAIRE
                ? Reservation::where('statut', Reservation::EN_ATTENTE)->count() : 0,
        ]);
    }
}
