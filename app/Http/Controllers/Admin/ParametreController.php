<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parametre;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParametreController extends Controller
{
    public function edit(ReservationService $service)
    {
        return Inertia::render('Admin/Parametres', [
            'parametres' => ['limite' => $service->limite(), 'duree_max' => $service->dureeMaxJours()],
        ]);
    }

    /** Use case « Configurer la limite de réservations actives » (+ durée maximale d'un emprunt). */
    public function update(Request $request)
    {
        $data = $request->validate([
            'limite' => ['required', 'integer', 'min:1', 'max:20'],
            'duree_max' => ['required', 'integer', 'min:1', 'max:30'],
        ], [
            'limite.required' => 'Indiquez le nombre maximal de réservations actives.',
            'duree_max.required' => "Indiquez la durée maximale d'un emprunt.",
            'duree_max.max' => "La durée maximale ne peut pas dépasser 30 jours.",
        ]);

        Parametre::definir(Parametre::LIMITE_RESERVATIONS, $data['limite'], $request->user()->id);
        Parametre::definir(Parametre::DUREE_MAX_JOURS, $data['duree_max'], $request->user()->id);

        return back()->with('success', 'Paramètres enregistrés : ils s\'appliquent aux prochaines demandes.');
    }
}
