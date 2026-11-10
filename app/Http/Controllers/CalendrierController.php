<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendrierController extends Controller
{
    public function index()
    {
        return Inertia::render('Calendrier/Index', [
            'equipements' => Equipement::withSum('pannesActives as panne_qty', 'quantite')->orderBy('nom')->get()
                ->map(fn ($e) => ['id' => $e->id, 'nom' => $e->nom, 'capacite' => max(0, $e->quantite_totale - $e->quantiteEnPanne()), 'total' => $e->quantite_totale]),
            'global' => auth()->user()->role === User::GESTIONNAIRE,
        ]);
    }

    /** JSON : réservations qui chevauchent [start, end). Le demandeur ne voit pas les noms des autres. */
    public function evenements(Request $request)
    {
        $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date']]);
        $debut = Carbon::parse($request->start)->startOfDay();
        $fin = Carbon::parse($request->end)->startOfDay();
        $user = $request->user();
        $gestion = $user->role === User::GESTIONNAIRE;

        $statuts = $gestion
            ? [Reservation::EN_ATTENTE, Reservation::ACCEPTEE, Reservation::TERMINEE, Reservation::RENDUE, Reservation::ALTERNATIVE]
            : [Reservation::ACCEPTEE, Reservation::TERMINEE, Reservation::RENDUE];

        $evenements = Reservation::with(['equipement', 'demandeur'])
            ->where(function ($q) use ($statuts, $gestion, $user) {
                $q->whereIn('statut', $statuts);
                if (! $gestion) {
                    $q->orWhere(fn ($w) => $w->where('user_id', $user->id)->whereIn('statut', [Reservation::EN_ATTENTE, Reservation::ALTERNATIVE]));
                }
            })
            ->where('date_debut', '<', $fin)->where('date_fin', '>', $debut)
            ->when($request->filled('equipement_id'), fn ($q) => $q->where('equipement_id', $request->equipement_id))
            ->orderBy('date_debut')->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'titre' => $r->equipement->nom,
                'equipement_id' => $r->equipement_id,
                'quantite' => $r->quantite,
                'statut' => $r->statut,
                'debut' => $r->date_debut->format('Y-m-d H:i:s'),
                'fin' => $r->date_fin->format('Y-m-d H:i:s'),
                'par' => $gestion ? $r->demandeur->name : ($r->user_id === $user->id ? 'Moi' : null),
                'mine' => $r->user_id === $user->id,
            ]);

        return response()->json($evenements);
    }
}
