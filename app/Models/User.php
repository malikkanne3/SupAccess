<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Classe User du diagramme. Demandeur, Gestionnaire et Admin sont des rôles (héritage → colonne `role`).
 */
class User extends Authenticatable
{
    public const DEMANDEUR = 'demandeur';
    public const GESTIONNAIRE = 'gestionnaire';
    public const ADMIN = 'admin';
    public const ROLES = [self::DEMANDEUR, self::GESTIONNAIRE, self::ADMIN];

    protected $fillable = ['name', 'email', 'password', 'role', 'service', 'actif'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'actif' => 'boolean',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    public function reservationsTraitees(): HasMany
    {
        return $this->hasMany(Reservation::class, 'gestionnaire_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function getInitialesAttribute(): string
    {
        $mots = preg_split('/\s+/', trim($this->name));
        $i = mb_substr($mots[0] ?? '', 0, 1);
        if (count($mots) > 1) {
            $i .= mb_substr(end($mots), 0, 1);
        }
        return mb_strtoupper($i);
    }

    public function aRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Réservations qui comptent dans la limite : tant que l'équipement n'est pas « rendu », il compte. */
    public function reservationsActives(?int $exclureId = null)
    {
        return $this->reservations()
            ->whereIn('statut', Reservation::ACTIVES)
            ->when($exclureId, fn ($q) => $q->where('id', '!=', $exclureId));
    }
}
