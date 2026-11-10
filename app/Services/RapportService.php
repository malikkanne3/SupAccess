<?php

namespace App\Services;

use App\Models\Equipement;
use App\Models\Reservation;
use Carbon\Carbon;

/**
 * Rapports d'utilisation (cahier, section 8) :
 *  - Taux d'utilisation (%) = jours réservés / jours de la période (normalisé par la quantité en stock)
 *  - Disponibilité (%)      = 100 − taux d'utilisation
 *  - Classement équipements = tri par nombre de réservations
 */
class RapportService
{
    public function calculer(Carbon $du, Carbon $au): array
    {
        $debut = $du->copy()->startOfDay();
        $fin = $au->copy()->endOfDay();
        $jours = max(1, (int) ceil($debut->diffInSeconds($fin) / 86400));

        $reservations = Reservation::with(['equipement.categorie', 'demandeur'])
            ->whereIn('statut', [Reservation::ACCEPTEE, Reservation::TERMINEE, Reservation::RENDUE])
            ->where('date_debut', '<', $fin)->where('date_fin', '>', $debut)
            ->get();

        $parEquipement = [];
        $parCategorie = [];
        $parService = [];

        foreach ($reservations as $r) {
            $a = $r->date_debut->greaterThan($debut) ? $r->date_debut : $debut;
            $b = $r->date_fin->lessThan($fin) ? $r->date_fin : $fin;
            $joursReserves = max(0, $a->diffInSeconds($b) / 86400) * $r->quantite;

            $e = $r->equipement;
            $parEquipement[$e->id]['nb'] = ($parEquipement[$e->id]['nb'] ?? 0) + 1;
            $parEquipement[$e->id]['jours'] = ($parEquipement[$e->id]['jours'] ?? 0) + $joursReserves;

            $cat = $e->categorie?->nom ?? 'Sans catégorie';
            $parCategorie[$cat]['jours'] = ($parCategorie[$cat]['jours'] ?? 0) + $joursReserves;

            $svc = $r->demandeur->service ?: 'Non renseigné';
            $parService[$svc]['nb'] = ($parService[$svc]['nb'] ?? 0) + 1;
            $parService[$svc]['jours'] = ($parService[$svc]['jours'] ?? 0) + $joursReserves;
        }

        $equipements = [];
        $capaciteCategorie = [];
        foreach (Equipement::with('categorie')->orderBy('nom')->get() as $e) {
            $d = $parEquipement[$e->id] ?? ['nb' => 0, 'jours' => 0];
            $capacite = max(1, $e->quantite_totale) * $jours;
            $taux = min(100, round($d['jours'] / $capacite * 100, 1));
            $equipements[] = [
                'id' => $e->id, 'nom' => $e->nom, 'categorie' => $e->categorie?->nom ?? 'Sans catégorie',
                'reservations' => $d['nb'], 'jours_reserves' => round($d['jours'], 1),
                'taux_utilisation' => $taux, 'disponibilite' => round(100 - $taux, 1),
            ];
            $cat = $e->categorie?->nom ?? 'Sans catégorie';
            $capaciteCategorie[$cat] = ($capaciteCategorie[$cat] ?? 0) + $capacite;
        }
        usort($equipements, fn ($x, $y) => [$y['reservations'], $y['jours_reserves']] <=> [$x['reservations'], $x['jours_reserves']]);

        $categories = [];
        foreach ($capaciteCategorie as $nom => $cap) {
            $j = $parCategorie[$nom]['jours'] ?? 0;
            $categories[] = ['nom' => $nom, 'jours_reserves' => round($j, 1), 'taux_utilisation' => min(100, round($j / $cap * 100, 1))];
        }
        usort($categories, fn ($x, $y) => $y['taux_utilisation'] <=> $x['taux_utilisation']);

        $services = [];
        foreach ($parService as $nom => $d) {
            $services[] = ['nom' => $nom, 'reservations' => $d['nb'], 'jours_reserves' => round($d['jours'], 1)];
        }
        usort($services, fn ($x, $y) => $y['reservations'] <=> $x['reservations']);

        $creees = Reservation::whereBetween('created_at', [$debut, $fin]);
        $total = (clone $creees)->count();
        $acceptees = (clone $creees)->whereIn('statut', [Reservation::ACCEPTEE, Reservation::TERMINEE, Reservation::RENDUE])->count();

        return [
            'du' => $debut->toDateString(),
            'au' => $fin->toDateString(),
            'jours' => $jours,
            'resume' => [
                'demandes' => $total,
                'acceptees' => $acceptees,
                'refusees' => (clone $creees)->where('statut', Reservation::REFUSEE)->count(),
                'annulees' => (clone $creees)->where('statut', Reservation::ANNULEE)->count(),
                'taux_acceptation' => $total ? round($acceptees / $total * 100) : 0,
            ],
            'equipements' => $equipements,
            'categories' => $categories,
            'services' => $services,
        ];
    }
}
