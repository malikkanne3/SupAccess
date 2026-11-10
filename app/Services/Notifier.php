<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Notification en base (cloche) + e-mail Laravel. Un échec d'envoi ne bloque jamais l'action métier. */
class Notifier
{
    public static function envoyer(User $user, string $message, string $type = 'info', ?string $lien = null, bool $mail = true): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'message' => $message,
            'lien' => $lien,
        ]);

        if ($mail && $user->email) {
            try {
                Mail::to($user->email)->send(new NotificationMail($user->name, $message, $lien ? url($lien) : null));
            } catch (\Throwable $e) {
                Log::warning('SupAccess : e-mail non envoyé à '.$user->email.' — '.$e->getMessage());
            }
        }
    }

    public static function gestionnaires(string $message, string $type = 'info', ?string $lien = null, bool $mail = true): void
    {
        User::where('role', User::GESTIONNAIRE)->where('actif', true)->get()
            ->each(fn (User $g) => self::envoyer($g, $message, $type, $lien, $mail));
    }
}
