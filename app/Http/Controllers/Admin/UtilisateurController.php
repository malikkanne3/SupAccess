<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UtilisateurController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withCount('reservations')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->orderBy('role')->orderBy('name')
            ->paginate(12)->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role,
                'service' => $u->service, 'actif' => $u->actif, 'reservations' => $u->reservations_count,
                'created_at' => $u->created_at->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Admin/Utilisateurs', ['users' => $users, 'filtres' => $request->only('q', 'role')]);
    }

    /** Use case « Créer les comptes admin et gestionnaire ». */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in([User::GESTIONNAIRE, User::ADMIN])],
            'service' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.unique' => 'Un compte existe déjà avec cette adresse e-mail.',
            'password.min' => 'Le mot de passe provisoire doit contenir au moins 8 caractères.',
        ]);

        User::create(array_merge($data, ['actif' => true, 'password' => Hash::make($data['password'])]));

        return back()->with('success', 'Compte créé. Communiquez le mot de passe provisoire à la personne concernée.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(User::ROLES)],
            'actif' => ['required', 'boolean'],
        ]);

        if ($user->id === $request->user()->id && ($data['role'] !== User::ADMIN || ! $data['actif'])) {
            return back()->with('error', 'Vous ne pouvez pas retirer vos propres droits ni désactiver votre compte.');
        }

        $user->update($data);

        return back()->with('success', 'Utilisateur mis à jour.');
    }
}
