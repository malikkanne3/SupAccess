<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    // Enum Statut_Reservation du diagramme (+ ALTERNATIVE : proposition en attente de confirmation du demandeur).
    public const EN_ATTENTE = 'EN_ATTENTE';
    public const ACCEPTEE = 'ACCEPTEE';
    public const REFUSEE = 'REFUSEE';
    public const TERMINEE = 'TERMINEE';
    public const RENDUE = 'RENDUE';
    public const ANNULEE = 'ANNULEE';
    public const ALTERNATIVE = 'ALTERNATIVE';

    /** Comptent dans la limite de réservations actives (tant que l'équipement n'est pas rendu). */
    public const ACTIVES = [self::EN_ATTENTE, self::ACCEPTEE, self::TERMINEE, self::ALTERNATIVE];

    protected $fillable = [
        'equipement_id', 'user_id', 'gestionnaire_id', 'equipement_propose_id', 'quantite',
        'date_debut', 'date_fin', 'motif', 'statut', 'motif_refus', 'rendu_at',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'rendu_at' => 'datetime',
        'quantite' => 'integer',
    ];

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function equipementPropose(): BelongsTo
    {
        return $this->belongsTo(Equipement::class, 'equipement_propose_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionnaire_id');
    }

    public function recommandations(): HasMany
    {
        return $this->hasMany(Recommandation::class);
    }

    /* ---- Transitions d'état (méthodes du diagramme) ---- */

    private function exiger(array $statuts, string $action): void
    {
        if (! in_array($this->statut, $statuts, true)) {
            throw new DomainException("Action « {$action} » impossible : la réservation est au statut {$this->statut}.");
        }
    }

    public function accepter(User $gestionnaire): void
    {
        $this->exiger([self::EN_ATTENTE], 'accepter');
        $this->update(['statut' => self::ACCEPTEE, 'gestionnaire_id' => $gestionnaire->id, 'motif_refus' => null]);
    }

    public function refuser(User $gestionnaire, string $raison): void
    {
        $this->exiger([self::EN_ATTENTE, self::ACCEPTEE, self::ALTERNATIVE], 'refuser');
        $this->update([
            'statut' => $this->statut === self::EN_ATTENTE ? self::REFUSEE : self::ANNULEE,
            'gestionnaire_id' => $gestionnaire->id,
            'motif_refus' => $raison,
            'equipement_propose_id' => null,
        ]);
    }

    public function annuler(?string $raison = null): void
    {
        $this->exiger([self::EN_ATTENTE, self::ACCEPTEE, self::ALTERNATIVE], 'annuler');
        $this->update(['statut' => self::ANNULEE, 'motif_refus' => $raison, 'equipement_propose_id' => null]);
    }

    public function modifier(array $donnees): void
    {
        $this->exiger([self::EN_ATTENTE], 'modifier');
        $this->update($donnees);
    }

    public function terminer(): void
    {
        $this->exiger([self::ACCEPTEE], 'terminer');
        $this->update(['statut' => self::TERMINEE]);
    }

    public function marquerRendu(User $gestionnaire): void
    {
        $this->exiger([self::ACCEPTEE, self::TERMINEE], 'marquer rendu');
        $this->update(['statut' => self::RENDUE, 'rendu_at' => now(), 'gestionnaire_id' => $gestionnaire->id]);
    }

    /** Représentation envoyée au front (dates en heure locale, sans conversion de fuseau). */
    public function dto(): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'quantite' => $this->quantite,
            'motif' => $this->motif,
            'motif_refus' => $this->motif_refus,
            'date_debut' => $this->date_debut->format('Y-m-d H:i:s'),
            'date_fin' => $this->date_fin->format('Y-m-d H:i:s'),
            'rendu_at' => $this->rendu_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'equipement' => $this->equipement ? [
                'id' => $this->equipement->id,
                'nom' => $this->equipement->nom,
                'image_url' => $this->equipement->image_url,
            ] : null,
            'propose' => $this->equipementPropose ? [
                'id' => $this->equipementPropose->id,
                'nom' => $this->equipementPropose->nom,
            ] : null,
        ];
    }
}
