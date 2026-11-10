<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Profil/Edit', [
            'profil' => ['name' => $user->name, 'email' => $user->email, 'service' => $user->service, 'role' => $user->role, 'depuis' => $user->created_at->format('Y-m-d H:i:s')],
            'stats' => [
                'total' => $user->reservations()->count(),
                'rendues' => $user->reservations()->where('statut', Reservation::RENDUE)->count(),
                'actives' => $user->reservationsActives()->count(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'service' => ['nullable', 'string', 'max:120'],
        ], ['name.required' => 'Votre nom ne peut pas être vide.']);

        $request->user()->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function motDePasse(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Saisissez votre mot de passe actuel.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Mot de passe modifié.');
    }
}
