<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Models\Panne;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RapportService;
use App\Services\RecommendationService;
use App\Services\ReservationService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(ReservationService $service, RecommendationService $ia, RapportService $rapports)
    {
        $service->cloturerExpirees(); // filet de sécurité si le planificateur n'est pas lancé
        $user = auth()->user();

        $data = match ($user->role) {
            User::GESTIONNAIRE => $this->gestionnaire($ia),
            User::ADMIN => $this->admin($rapports, $service),
            default => $this->demandeur($user, $service),
        };

        return Inertia::render('Dashboard', ['role' => $user->role, 'data' => $data]);
    }

    private function demandeur(User $user, ReservationService $service): array
    {
        $mes = $user->reservations()->with('equipement')->get();
        $compte = fn (string $s) => $mes->where('statut', $s)->count();

        return [
            'actives' => $user->reservationsActives()->count(),
            'limite' => $service->limite(),
            'en_attente' => $compte(Reservation::EN_ATTENTE),
            'acceptees' => $compte(Reservation::ACCEPTEE),
            'a_rendre' => $mes->where('statut', Reservation::TERMINEE)->map->dto()->values(),
            'alternatives' => $mes->where('statut', Reservation::ALTERNATIVE)->count(),
            'prochaines' => $mes->whereIn('statut', [Reservation::ACCEPTEE, Reservation::EN_ATTENTE, Reservation::ALTERNATIVE])
                ->sortBy('date_debut')->take(5)->map->dto()->values(),
            'disponibles' => Equipement::with('categorie')->withSum('pannesActives as panne_qty', 'quantite')->orderBy('nom')->get()
                ->map->toCard()->filter(fn ($e) => $e['quantite_disponible'] > 0)->take(6)->values(),
        ];
    }

    private function gestionnaire(RecommendationService $ia): array
    {
        $unites = ['total' => 0, 'panne' => 0, 'reserve' => 0, 'dispo' => 0];
        $etats = ['DISPO' => 0, 'RESERVE' => 0, 'PANNE' => 0];
        foreach (Equipement::withSum('pannesActives as panne_qty', 'quantite')->get() as $e) {
            $dispo = $e->quantiteDisponible();
            $panne = min($e->quantiteEnPanne(), $e->quantite_totale);
            $unites['total'] += $e->quantite_totale;
            $unites['panne'] += $panne;
            $unites['dispo'] += $dispo;
            $unites['reserve'] += max(0, $e->quantite_totale - $panne - $dispo);
            $etats[$e->etat($dispo)]++;
        }

        $attente = Reservation::with(['equipement.categorie', 'demandeur'])->where('statut', Reservation::EN_ATTENTE)->orderBy('created_at')->get();

        return [
            'unites' => $unites,
            'etats' => $etats,
            'nb_attente' => $attente->count(),
            'nb_cours' => Reservation::where('statut', Reservation::ACCEPTEE)->count(),
            'nb_retours' => Reservation::where('statut', Reservation::TERMINEE)->count(),
            'nb_pannes' => Panne::whereNull('resolue_at')->count(),
            'attente' => $attente->take(5)->map(fn ($r) => $r->dto() + [
                'demandeur' => $r->demandeur->name,
                'service' => $r->demandeur->service,
                'ia' => $ia->analyser($r)['type'],
            ])->values(),
            'retours' => Reservation::with(['equipement', 'demandeur'])->where('statut', Reservation::TERMINEE)->orderBy('date_fin')->take(5)->get()
                ->map(fn ($r) => $r->dto() + ['demandeur' => $r->demandeur->name, 'service' => $r->demandeur->service])->values(),
        ];
    }

    private function admin(RapportService $rapports, ReservationService $service): array
    {
        $r = $rapports->calculer(now()->subDays(29), now());

        return [
            'utilisateurs' => [
                'demandeur' => User::where('role', User::DEMANDEUR)->count(),
                'gestionnaire' => User::where('role', User::GESTIONNAIRE)->count(),
                'admin' => User::where('role', User::ADMIN)->count(),
                'inactifs' => User::where('actif', false)->count(),
            ],
            'nb_equipements' => Equipement::count(),
            'resume' => $r['resume'],
            'top' => array_slice($r['equipements'], 0, 5),
            'limite' => $service->limite(),
            'duree_max' => $service->dureeMaxJours(),
        ];
    }
}
