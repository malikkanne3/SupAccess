<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Models\Panne;
use App\Models\Reservation;
use App\Services\Notifier;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PanneController extends Controller
{
    public function index()
    {
        return Inertia::render('Pannes/Index', [
            'pannes' => Panne::with('equipement')->orderByRaw('resolue_at is null desc')->latest()->take(80)->get()->map(fn ($p) => [
                'id' => $p->id,
                'equipement' => $p->equipement->nom,
                'equipement_id' => $p->equipement_id,
                'quantite' => $p->quantite,
                'description' => $p->description,
                'declaree' => $p->created_at->format('Y-m-d H:i:s'),
                'resolue' => $p->resolue_at?->format('Y-m-d H:i:s'),
            ]),
            'equipements' => Equipement::withSum('pannesActives as panne_qty', 'quantite')->orderBy('nom')->get()
                ->map(fn ($e) => ['id' => $e->id, 'nom' => $e->nom, 'sain' => max(0, $e->quantite_totale - $e->quantiteEnPanne())]),
        ]);
    }

    /** Déclarer une panne : réduit le stock disponible, signale les réservations impactées (le gestionnaire décide). */
    public function store(Request $request, Equipement $equipement, RecommendationService $ia)
    {
        $saines = max(0, $equipement->quantite_totale - $equipement->quantiteEnPanne());
        if ($saines < 1) {
            return back()->with('error', 'Tous les exemplaires de cet équipement sont déjà déclarés en panne.');
        }

        $data = $request->validate([
            'quantite' => ['required', 'integer', 'min:1', 'max:'.$saines],
            'description' => ['required', 'string', 'max:500'],
        ], [
            'quantite.max' => "Il ne reste que {$saines} exemplaire(s) en état de marche.",
            'description.required' => 'Décrivez brièvement la panne.',
        ]);

        $equipement->pannes()->create($data);

        $impactees = $equipement->reservations()->with('equipement')->where('statut', Reservation::ACCEPTEE)
            ->where('date_fin', '>', now())->get()
            ->filter(fn ($r) => $equipement->quantiteDisponible($r->date_debut, $r->date_fin, $r->id) < $r->quantite);
        $impactees->each(fn ($r) => $ia->enregistrer($r));

        if ($impactees->isNotEmpty()) {
            Notifier::gestionnaires("Panne sur « {$equipement->nom} » : {$impactees->count()} réservation(s) acceptée(s) impactée(s). Annulez-les ou proposez un autre équipement.", 'danger', '/demandes?vue=cours');
        }

        $recentes = $equipement->pannes()->where('created_at', '>=', now()->subDays(60))->count();
        if ($recentes >= 3) {
            Notifier::gestionnaires("Alerte : « {$equipement->nom} » est tombé en panne {$recentes} fois en 60 jours. Envisagez un remplacement.", 'warning', '/equipements/'.$equipement->id);
        }

        return back()->with('success', 'Panne enregistrée'.($impactees->isNotEmpty() ? " — {$impactees->count()} réservation(s) à traiter." : '.'));
    }

    public function resoudre(Panne $panne)
    {
        $panne->resoudre();

        return back()->with('success', 'Panne marquée comme résolue : le stock est de nouveau disponible.');
    }
}
