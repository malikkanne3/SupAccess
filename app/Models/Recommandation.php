<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Classe Recommendation_IA du diagramme. */
class Recommandation extends Model
{
    protected $table = 'recommandations_ia';

    protected $fillable = ['reservation_id', 'type', 'contenu', 'decision_gestionnaire'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
