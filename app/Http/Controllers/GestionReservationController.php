<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Models\Reservation;
use App\Services\RecommendationService;
use App\Services\ReservationService;
use DomainException;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Côté Gestionnaire : demandes en attente, recommandation IA, acceptation/refus, alternative, rendu, relance. */
class GestionReservationController extends Controller
{
    public function __construct(private ReservationService $service, private RecommendationService $ia)
    {
    }

    public function index(Request $request)
    {
        $this->service->cloturerExpirees();
        $vue = in_array($request->vue, ['attente', 'cours', 'retours', 'toutes'], true) ? $request->vue : 'attente';

        $requete = Reservation::with(['equipement.categorie', 'equipementPropose', 'demandeur'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $mot = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->whereHas('demandeur', fn ($u) => $u->where('name', 'like', $mot))
                    ->orWhereHas('equipement', fn ($e) => $e->where('nom', 'like', $mot)));
            });

        $liste = match ($vue) {
            'attente' => (clone $requete)->where('statut', Reservation::EN_ATTENTE)->orderBy('created_at'),
            'cours' => (clone $requete)->whereIn('statut', [Reservation::ACCEPTEE, Reservation::ALTERNATIVE])->orderBy('date_debut'),
            'retours' => (clone $requete)->where('statut', Reservation::TERMINEE)->orderBy('date_fin'),
            default => (clone $requete)->latest('created_at'),
        };

        $reservations = $liste->take(60)->get()->map(function (Reservation $r) use ($vue) {
            $extra = [
                'demandeur' => $r->demandeur->name,
                'email' => $r->demandeur->email,
                'service' => $r->demandeur->service,
                'peut_rendre' => in_array($r->statut, [Reservation::ACCEPTEE, Reservation::TERMINEE], true),
                'retard_heures' => $r->statut === Reservation::TERMINEE ? max(0, (int) $r->date_fin->diffInHours(now())) : null,
            ];
            if ($vue === 'attente') {
                $a = $this->ia->analyser($r);
                $extra['ia'] = [
                    'type' => $a['type'],
                    'libelle' => RecommendationService::LIBELLES[$a['type']],
                    'contenu' => $a['contenu'],
                    'alternative_id' => $a['alternative_id'],
                ];
            }
            if ($r->statut === Reservation::ACCEPTEE && $r->date_fin->isFuture()) {
                // Réservation impactée (ex. panne déclarée après l'acceptation) : le stock ne couvre plus la demande.
                $extra['impactee'] = $r->equipement->quantiteDisponible($r->date_debut, $r->date_fin, $r->id) < $r->quantite;
            }
            return $r->dto() + $extra;
        });

        return Inertia::render('Reservations/Gestion', [
            'vue' => $vue,
            'reservations' => $reservations,
            'compteurs' => [
                'attente' => Reservation::where('statut', Reservation::EN_ATTENTE)->count(),
                'cours' => Reservation::whereIn('statut', [Reservation::ACCEPTEE, Reservation::ALTERNATIVE])->count(),
                'retours' => Reservation::where('statut', Reservation::TERMINEE)->count(),
            ],
            'q' => $request->q,
        ]);
    }

    /** JSON : équipements libres sur la période de la réservation (modale « Proposer un autre équipement »). */
    public function alternatives(Reservation $reservation)
    {
        $liste = Equipement::with('categorie')->where('id', '!=', $reservation->equipement_id)->orderBy('nom')->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'nom' => $e->nom,
                'categorie' => $e->categorie?->nom,
                'meme_categorie' => $e->categorie_id && $e->categorie_id === $reservation->equipement->categorie_id,
                'disponible' => $e->quantiteDisponible($reservation->date_debut, $reservation->date_fin),
            ])
            ->filter(fn ($e) => $e['disponible'] >= $reservation->quantite)
            ->sortByDesc('meme_categorie')->values();

        return response()->json($liste);
    }

    public function accepter(Request $request, Reservation $reservation)
    {
        return $this->agir(fn () => $this->service->accepter($request->user(), $reservation->load('equipement', 'demandeur')), 'Réservation acceptée. Le demandeur est prévenu.');
    }

    public function refuser(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['motif_refus' => ['required', 'string', 'max:250']], ['motif_refus.required' => 'Indiquez le motif : il sera communiqué au demandeur.']);

        return $this->agir(fn () => $this->service->refuser($request->user(), $reservation->load('equipement', 'demandeur'), $data['motif_refus']), 'Décision enregistrée. Le demandeur est prévenu.');
    }

    public function alternative(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['equipement_id' => ['required', 'exists:equipements,id']], ['equipement_id.required' => 'Choisissez un équipement de remplacement.']);

        return $this->agir(fn () => $this->service->proposerAlternative($request->user(), $reservation->load('equipement', 'demandeur'), (int) $data['equipement_id']), 'Alternative proposée : en attente de la réponse du demandeur.');
    }

    public function rendu(Request $request, Reservation $reservation)
    {
        return $this->agir(fn () => $this->service->marquerRendu($request->user(), $reservation->load('equipement', 'demandeur')), 'Équipement marqué comme rendu.');
    }

    public function relancer(Reservation $reservation)
    {
        return $this->agir(fn () => $this->service->relancer($reservation->load('equipement', 'demandeur')), 'Relance envoyée au demandeur (notification et e-mail).');
    }

    private function agir(callable $action, string $succes)
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $succes);
    }
}
