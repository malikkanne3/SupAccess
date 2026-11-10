<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => $request->user()->notifications()->take(100)->get()->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'message' => $n->message,
                'lien' => $n->lien,
                'lu' => (bool) $n->lu_at,
                'date' => $n->created_at->format('Y-m-d H:i:s'),
            ]),
        ]);
    }

    public function lire(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['lu_at' => $notification->lu_at ?? now()]);

        return $notification->lien ? redirect($notification->lien) : back();
    }

    public function toutLire(Request $request)
    {
        $request->user()->notifications()->whereNull('lu_at')->update(['lu_at' => now()]);

        return back()->with('success', 'Toutes les notifications sont marquées comme lues.');
    }
}
