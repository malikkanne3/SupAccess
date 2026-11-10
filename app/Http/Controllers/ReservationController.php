<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Models\Reservation;
use App\Services\ReservationService;
use DomainException;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Côté Demandeur : réserver, suivre, modifier une demande en attente, annuler, historique. */
class ReservationController extends Controller
{
    public function __construct(private ReservationService $service)
    {
    }

    public function mes()
    {
        $user = auth()->user();
        $this->service->cloturerExpirees();

        return Inertia::render('Reservations/Mes', [
            'reservations' => $user->reservations()->with(['equipement', 'equipementPropose'])->latest('date_debut')->get()
                ->map(fn ($r) => $r->dto() + [
                    'peut_modifier' => $r->statut === Reservation::EN_ATTENTE,
                    'peut_annuler' => in_array($r->statut, [Reservation::EN_ATTENTE, Reservation::ALTERNATIVE], true)
                        || ($r->statut === Reservation::ACCEPTEE && $r->date_debut->isFuture()),
                ]),
            'limite' => $this->service->limite(),
            'actives' => $user->reservationsActives()->count(),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Reservations/Form', $this->contexte() + [
            'reservation' => null,
            'equipement_id' => $request->integer('equipement') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);

        try {
            $this->service->creer($request->user(), $data);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect('/mes-reservations')->with('success', 'Demande envoyée. Le gestionnaire va l\'examiner.');
    }

    public function edit(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id() && $reservation->statut === Reservation::EN_ATTENTE, 403);

        return Inertia::render('Reservations/Form', $this->contexte() + [
            'reservation' => [
                'id' => $reservation->id,
                'equipement_id' => $reservation->equipement_id,
                'quantite' => $reservation->quantite,
                'date_debut' => $reservation->date_debut->format('Y-m-d H:i:s'),
                'date_fin' => $reservation->date_fin->format('Y-m-d H:i:s'),
                'motif' => $reservation->motif,
            ],
            'equipement_id' => $reservation->equipement_id,
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $data = $this->valider($request);

        try {
            $this->service->modifier($request->user(), $reservation, $data);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect('/mes-reservations')->with('success', 'Votre demande a été modifiée.');
    }

    public function annuler(Request $request, Reservation $reservation)
    {
        try {
            $this->service->annuler($request->user(), $reservation->load('equipement'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Réservation annulée.');
    }

    public function alternative(Request $request, Reservation $reservation)
    {
        $accepte = $request->boolean('accepte');
        try {
            $this->service->repondreAlternative($request->user(), $reservation->load(['equipement', 'equipementPropose']), $accepte);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $accepte ? 'Alternative acceptée : votre réservation est confirmée.' : 'Alternative refusée : la réservation est annulée.');
    }

    private function contexte(): array
    {
        return [
            'equipements' => Equipement::with('categorie')->withSum('pannesActives as panne_qty', 'quantite')->orderBy('nom')->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'nom' => $e->nom,
                    'categorie' => $e->categorie?->nom,
                    'quantite_totale' => $e->quantite_totale,
                    'quantite_disponible' => $e->quantiteDisponible(),
                    'en_panne_totale' => $e->quantiteEnPanne() >= $e->quantite_totale,
                ]),
            'regles' => [
                'limite' => $this->service->limite(),
                'duree_max' => $this->service->dureeMaxJours(),
                'actives' => auth()->user()->reservationsActives()->count(),
            ],
        ];
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'equipement_id' => ['required', 'exists:equipements,id'],
            'quantite' => ['required', 'integer', 'min:1', 'max:1000'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date'],
            'motif' => ['required', 'string', 'max:500'],
        ], [
            'equipement_id.required' => 'Choisissez un équipement.',
            'quantite.required' => 'Indiquez la quantité souhaitée.',
            'date_debut.required' => 'Indiquez la date et l\'heure de début.',
            'date_fin.required' => 'Indiquez la date et l\'heure de fin.',
            'motif.required' => 'Expliquez brièvement le motif de la demande.',
        ]);
    }
}
