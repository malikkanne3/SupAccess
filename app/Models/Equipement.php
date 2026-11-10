<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Equipement extends Model
{
    protected $fillable = ['categorie_id', 'nom', 'description', 'image', 'quantite_totale'];

    protected $casts = ['quantite_totale' => 'integer'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function pannes(): HasMany
    {
        return $this->hasMany(Panne::class);
    }

    public function pannesActives(): HasMany
    {
        return $this->hasMany(Panne::class)->whereNull('resolue_at');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function quantiteEnPanne(): int
    {
        // `panne_qty` est pré-chargé par withSum() dans les listes (évite le N+1).
        if (array_key_exists('panne_qty', $this->attributes)) {
            return (int) $this->attributes['panne_qty'];
        }
        return (int) $this->pannesActives()->sum('quantite');
    }

    /**
     * QuantiteDisponible() du diagramme.
     * Stock total − exemplaires en panne − exemplaires déjà pris sur la période.
     * Bloquent : les réservations ACCEPTEE qui chevauchent la période, et les TERMINEE non rendues
     * (l'équipement n'est physiquement pas revenu).
     */
    public function quantiteDisponible(?Carbon $debut = null, ?Carbon $fin = null, ?int $exclureId = null): int
    {
        $debut = $debut ? $debut->copy() : now();
        $fin = $fin ? $fin->copy() : now()->addMinute();

        $prises = (int) $this->reservations()
            ->when($exclureId, fn ($q) => $q->where('id', '!=', $exclureId))
            ->where(function ($q) use ($debut, $fin) {
                $q->where(function ($w) use ($debut, $fin) {
                    $w->where('statut', Reservation::ACCEPTEE)
                        ->where('date_debut', '<', $fin)
                        ->where('date_fin', '>', $debut);
                })->orWhere(function ($w) use ($fin) {
                    $w->where('statut', Reservation::TERMINEE)
                        ->where('date_debut', '<', $fin);
                });
            })
            ->sum('quantite');

        return max(0, $this->quantite_totale - $this->quantiteEnPanne() - $prises);
    }

    /** État affiché (cahier : DISPO | RESERVE | PANNE), calculé à l'instant T. */
    public function etat(?int $dispoMaintenant = null): string
    {
        $dispo = $dispoMaintenant ?? $this->quantiteDisponible();
        if ($this->quantiteEnPanne() >= $this->quantite_totale) {
            return 'PANNE';
        }
        return $dispo > 0 ? 'DISPO' : 'RESERVE';
    }

    public function toCard(): array
    {
        $dispo = $this->quantiteDisponible();
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'categorie' => $this->categorie ? ['id' => $this->categorie->id, 'nom' => $this->categorie->nom] : null,
            'quantite_totale' => $this->quantite_totale,
            'quantite_disponible' => $dispo,
            'quantite_en_panne' => $this->quantiteEnPanne(),
            'etat' => $this->etat($dispo),
        ];
    }
}
