<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EquipementController extends Controller
{
    public function index(Request $request)
    {
        $equipements = Equipement::with('categorie')->withSum('pannesActives as panne_qty', 'quantite')
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%'.$request->q.'%'))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie_id', $request->categorie))
            ->orderBy('nom')->get()
            ->map->toCard()
            ->when($request->filled('etat'), fn ($c) => $c->where('etat', $request->etat))
            ->values();

        return Inertia::render('Equipements/Index', [
            'equipements' => $equipements,
            'categories' => Categorie::orderBy('nom')->get(['id', 'nom']),
            'filtres' => $request->only('q', 'categorie', 'etat'),
        ]);
    }

    public function show(Equipement $equipement)
    {
        $equipement->load('categorie');
        $gestion = auth()->user()->role === User::GESTIONNAIRE;

        $historique = $equipement->reservations()->with('demandeur')
            ->whereIn('statut', $gestion
                ? [Reservation::ACCEPTEE, Reservation::TERMINEE, Reservation::RENDUE, Reservation::REFUSEE, Reservation::ANNULEE, Reservation::EN_ATTENTE]
                : [Reservation::ACCEPTEE, Reservation::TERMINEE, Reservation::RENDUE])
            ->latest('date_debut')->take(15)->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'statut' => $r->statut,
                'quantite' => $r->quantite,
                'date_debut' => $r->date_debut->format('Y-m-d H:i:s'),
                'date_fin' => $r->date_fin->format('Y-m-d H:i:s'),
                'demandeur' => $gestion ? $r->demandeur->name : null,
                'service' => $r->demandeur->service,
            ]);

        return Inertia::render('Equipements/Show', [
            'equipement' => $equipement->toCard(),
            'historique' => $historique,
            'pannes' => $gestion ? $equipement->pannes()->latest()->take(10)->get()->map(fn ($p) => [
                'id' => $p->id, 'quantite' => $p->quantite, 'description' => $p->description,
                'declaree' => $p->created_at->format('Y-m-d H:i:s'),
                'resolue' => $p->resolue_at?->format('Y-m-d H:i:s'),
            ]) : [],
        ]);
    }

    /** JSON : disponibilité sur une période (formulaire de réservation, vérification en direct). */
    public function disponibilite(Request $request, Equipement $equipement)
    {
        try {
            $debut = $request->filled('debut') ? Carbon::parse($request->debut) : null;
            $fin = $request->filled('fin') ? Carbon::parse($request->fin) : null;
        } catch (\Throwable) {
            $debut = $fin = null;
        }
        if ($debut && $fin && $debut->gte($fin)) {
            $debut = $fin = null;
        }

        return response()->json([
            'disponible' => $equipement->quantiteDisponible($debut, $fin, $request->integer('exclure') ?: null),
            'total' => $equipement->quantite_totale,
            'en_panne' => $equipement->quantiteEnPanne(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Equipements/Form', [
            'equipement' => null,
            'categories' => Categorie::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('equipements', 'public');
        }
        $e = Equipement::create($data);

        return redirect('/equipements/'.$e->id)->with('success', "Équipement « {$e->nom} » ajouté.");
    }

    public function edit(Equipement $equipement)
    {
        return Inertia::render('Equipements/Form', [
            'equipement' => $equipement->toCard() + ['categorie_id' => $equipement->categorie_id],
            'categories' => Categorie::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function update(Request $request, Equipement $equipement)
    {
        $data = $this->valider($request);
        if ($request->hasFile('image')) {
            if ($equipement->image) {
                Storage::disk('public')->delete($equipement->image);
            }
            $data['image'] = $request->file('image')->store('equipements', 'public');
        } else {
            unset($data['image']);
        }
        $equipement->update($data);

        return redirect('/equipements/'.$equipement->id)->with('success', 'Équipement mis à jour.');
    }

    public function destroy(Equipement $equipement)
    {
        if ($equipement->reservations()->exists()) {
            return back()->with('error', "Suppression impossible : cet équipement a un historique de réservations. Réduisez plutôt sa quantité ou déclarez-le en panne.");
        }
        if ($equipement->image) {
            Storage::disk('public')->delete($equipement->image);
        }
        $nom = $equipement->nom;
        $equipement->delete();

        return redirect('/equipements')->with('success', "« {$nom} » a été supprimé.");
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'categorie_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'quantite_totale' => ['required', 'integer', 'min:1', 'max:1000'],
            'image' => ['nullable', 'image', 'max:3072'],
        ], [
            'nom.required' => "Donnez un nom précis à l'équipement (modèle compris).",
            'quantite_totale.required' => 'Indiquez la quantité en stock.',
            'quantite_totale.min' => 'La quantité doit être au moins 1.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => "L'image ne doit pas dépasser 3 Mo.",
        ]);
    }
}
