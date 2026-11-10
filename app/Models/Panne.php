<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panne extends Model
{
    protected $fillable = ['equipement_id', 'quantite', 'description', 'resolue_at'];

    protected $casts = ['resolue_at' => 'datetime'];

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    /** resoudre() du diagramme. */
    public function resoudre(): void
    {
        $this->update(['resolue_at' => now()]);
    }
}
