<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RapportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RapportController extends Controller
{
    public function index(Request $request, RapportService $service)
    {
        [$du, $au] = $this->periode($request);

        return Inertia::render('Admin/Rapports', ['rapport' => $service->calculer($du, $au)]);
    }

    /** Use case « Exporter les rapports » : CSV (séparateur « ; », UTF-8 avec BOM pour Excel). */
    public function export(Request $request, RapportService $service)
    {
        [$du, $au] = $this->periode($request);
        $r = $service->calculer($du, $au);

        return response()->streamDownload(function () use ($r) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            $ligne = fn (array $l) => fputcsv($f, $l, ';');

            $ligne(["Rapport d'utilisation SupAccess", "Du {$r['du']} au {$r['au']}"]);
            $ligne([]);
            $ligne(['Équipements les plus utilisés']);
            $ligne(['Équipement', 'Catégorie', 'Réservations', 'Jours réservés', "Taux d'utilisation (%)", 'Disponibilité (%)']);
            foreach ($r['equipements'] as $e) {
                $ligne([$e['nom'], $e['categorie'], $e['reservations'], $e['jours_reserves'], $e['taux_utilisation'], $e['disponibilite']]);
            }
            $ligne([]);
            $ligne(["Taux d'utilisation par catégorie"]);
            $ligne(['Catégorie', 'Jours réservés', "Taux d'utilisation (%)"]);
            foreach ($r['categories'] as $c) {
                $ligne([$c['nom'], $c['jours_reserves'], $c['taux_utilisation']]);
            }
            $ligne([]);
            $ligne(['Historique par service']);
            $ligne(['Service', 'Réservations', 'Jours réservés']);
            foreach ($r['services'] as $s) {
                $ligne([$s['nom'], $s['reservations'], $s['jours_reserves']]);
            }
            fclose($f);
        }, "rapport-supaccess-{$r['du']}-{$r['au']}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function periode(Request $request): array
    {
        try {
            $du = $request->filled('du') ? Carbon::parse($request->du) : now()->subDays(29);
            $au = $request->filled('au') ? Carbon::parse($request->au) : now();
        } catch (\Throwable) {
            $du = now()->subDays(29);
            $au = now();
        }
        if ($du->gt($au)) {
            [$du, $au] = [$au, $du];
        }

        return [$du, $au];
    }
}
