<?php

namespace App\Services;

use App\Models\Equipement;
use App\Models\Parametre;
use App\Models\Recommandation;
use App\Models\Reservation;

/**
 * « IA » d'aide à la décision : elle RECOMMANDE, le gestionnaire DÉCIDE (Recommendation_IA du diagramme).
 * Version à règles ; analyser() est le point d'entrée à remplacer par un appel à un vrai modèle plus tard.
 */
class RecommendationService
{
    public const LIBELLES = [
        'ACCEPTER' => 'Accepter',
        'REFUSER' => 'Refuser',
        'ALTERNATIVE' => 'Proposer une alternative',
        'ATTENTION' => 'Vigilance',
    ];

    /** Analyse pure (n'écrit rien) : type, contenu, alternative éventuelle. */
    public function analyser(Reservation $r): array
    {
        $e = $r->equipement;
        $dispo = $e->quantiteDisponible($r->date_debut, $r->date_fin, $r->id);

        if ($dispo < $r->quantite) {
            $alt = $this->alternative($r);
            if ($alt) {
                return [
                    'type' => 'ALTERNATIVE',
                    'contenu' => "Stock insuffisant sur la période ({$dispo} disponible(s) pour {$r->quantite} demandé(s)). "
                        ."Alternative libre dans la même catégorie : « {$alt['nom']} » ({$alt['disponible']} disponible(s)).",
                    'alternative_id' => $alt['id'],
                ];
            }
            return [
                'type' => 'REFUSER',
                'contenu' => "Stock insuffisant sur la période ({$dispo} disponible(s) pour {$r->quantite} demandé(s)) et aucun équipement équivalent n'est libre. Refus recommandé.",
                'alternative_id' => null,
            ];
        }

        $retards = $this->retardsDuDemandeur($r->user_id);
        if ($retards >= 1) {
            return [
                'type' => 'ATTENTION',
                'contenu' => "Disponible, mais ce demandeur cumule {$retards} retour(s) tardif(s) (plus de 24 h). Accepter avec vigilance ou lui rappeler les règles.",
                'alternative_id' => null,
            ];
        }

        $notes = ['Équipement disponible sur toute la période', 'limite de réservations respectée', 'aucun retard enregistré pour ce demandeur'];
        if ($dispo - $r->quantite === 0) {
            $notes[] = "attention : c'est le dernier stock disponible sur cette période";
        }
        $max = (int) Parametre::valeur(Parametre::DUREE_MAX_JOURS, 7);
        if ($max > 0 && $r->date_debut->diffInMinutes($r->date_fin) > 0.8 * $max * 1440) {
            $notes[] = 'durée proche du maximum autorisé';
        }

        return ['type' => 'ACCEPTER', 'contenu' => ucfirst(implode(', ', $notes)).'.', 'alternative_id' => null];
    }

    /** Enregistre la recommandation courante (non décidée) pour la réservation. */
    public function enregistrer(Reservation $r): Recommandation
    {
        $a = $this->analyser($r);
        Recommandation::where('reservation_id', $r->id)->whereNull('decision_gestionnaire')->delete();

        return Recommandation::create([
            'reservation_id' => $r->id,
            'type' => $a['type'],
            'contenu' => $a['contenu'],
        ]);
    }

    /** Trace la décision du gestionnaire face à la recommandation. */
    public function decision(Reservation $r, string $decision): void
    {
        $reco = Recommandation::where('reservation_id', $r->id)->whereNull('decision_gestionnaire')->latest('id')->first();
        $reco?->update(['decision_gestionnaire' => $decision]);
    }

    public function alternative(Reservation $r): ?array
    {
        $e = $r->equipement;
        if (! $e->categorie_id) {
            return null;
        }
        $candidats = Equipement::where('categorie_id', $e->categorie_id)->where('id', '!=', $e->id)->orderBy('nom')->get();
        foreach ($candidats as $c) {
            $d = $c->quantiteDisponible($r->date_debut, $r->date_fin);
            if ($d >= $r->quantite) {
                return ['id' => $c->id, 'nom' => $c->nom, 'disponible' => $d];
            }
        }
        return null;
    }

    private function retardsDuDemandeur(int $userId): int
    {
        return Reservation::where('user_id', $userId)->whereIn('statut', [Reservation::TERMINEE, Reservation::RENDUE])->get()
            ->filter(function (Reservation $x) {
                $reference = $x->statut === Reservation::RENDUE ? $x->rendu_at : now();
                return $reference && $x->date_fin->diffInHours($reference, false) > 24;
            })->count();
    }
}
