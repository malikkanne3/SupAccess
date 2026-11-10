<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Saisissez votre adresse e-mail.',
            'email.email' => "Cette adresse e-mail n'est pas valide.",
            'password.required' => 'Saisissez votre mot de passe.',
        ]);

        $utilisateur = User::where('email', $data['email'])->first();
        if ($utilisateur && ! $utilisateur->actif && Hash::check($data['password'], $utilisateur->password)) {
            throw ValidationException::withMessages(['email' => 'Ce compte est désactivé. Contactez la direction.']);
        }

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'actif' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou mot de passe incorrect.']);
        }

        $request->session()->regenerate();

        return redirect()->intended('/tableau-de-bord');
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    /** Use case « S'inscrire » : le demandeur crée lui-même son compte. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'service' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Saisissez votre nom complet.',
            'email.required' => 'Saisissez votre adresse e-mail.',
            'email.unique' => 'Un compte existe déjà avec cette adresse e-mail.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'service' => $data['service'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => User::DEMANDEUR,
            'actif' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/tableau-de-bord')->with('success', 'Bienvenue sur SupAccess, '.$user->name.' !');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/connexion');
    }
}
