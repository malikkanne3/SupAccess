<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Classe Paramètre (gérée par l'Admin). */
class Parametre extends Model
{
    public const LIMITE_RESERVATIONS = 'limite_reservations_actives';
    public const DUREE_MAX_JOURS = 'duree_max_jours';

    protected $fillable = ['cle', 'valeur', 'user_id'];

    public static function valeur(string $cle, $defaut = null)
    {
        return static::where('cle', $cle)->value('valeur') ?? $defaut;
    }

    public static function definir(string $cle, $valeur, ?int $userId = null): void
    {
        static::updateOrCreate(['cle' => $cle], ['valeur' => (string) $valeur, 'user_id' => $userId]);
    }
}
