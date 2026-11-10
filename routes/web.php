<?php

use App\Http\Controllers\Admin\ParametreController;
use App\Http\Controllers\Admin\RapportController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendrierController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipementController;
use App\Http\Controllers\GestionReservationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PanneController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tableau-de-bord');

/* Visiteur : s'inscrire / se connecter (use cases du demandeur) */
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login']);
    Route::get('/inscription', [AuthController::class, 'showRegister']);
    Route::post('/inscription', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthController::class, 'logout']);
    Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

    // Profil + notifications : tous les rôles
    Route::get('/profil', [ProfilController::class, 'edit']);
    Route::put('/profil', [ProfilController::class, 'update']);
    Route::put('/profil/mot-de-passe', [ProfilController::class, 'motDePasse']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/tout-lire', [NotificationController::class, 'toutLire']);
    Route::post('/notifications/{notification}/lire', [NotificationController::class, 'lire']);

    /* Gestionnaire d'équipements (déclaré avant les routes partagées pour /equipements/creer) */
    Route::middleware('role:gestionnaire')->group(function () {
        Route::get('/equipements/creer', [EquipementController::class, 'create']);
        Route::post('/equipements', [EquipementController::class, 'store']);
        Route::get('/equipements/{equipement}/modifier', [EquipementController::class, 'edit']);
        Route::put('/equipements/{equipement}', [EquipementController::class, 'update']);
        Route::delete('/equipements/{equipement}', [EquipementController::class, 'destroy']);

        Route::get('/categories', [CategorieController::class, 'index']);
        Route::post('/categories', [CategorieController::class, 'store']);
        Route::put('/categories/{categorie}', [CategorieController::class, 'update']);
        Route::delete('/categories/{categorie}', [CategorieController::class, 'destroy']);

        Route::get('/pannes', [PanneController::class, 'index']);
        Route::post('/equipements/{equipement}/pannes', [PanneController::class, 'store']);
        Route::post('/pannes/{panne}/resoudre', [PanneController::class, 'resoudre']);

        Route::get('/demandes', [GestionReservationController::class, 'index']);
        Route::get('/demandes/{reservation}/alternatives', [GestionReservationController::class, 'alternatives']);
        Route::post('/demandes/{reservation}/accepter', [GestionReservationController::class, 'accepter']);
        Route::post('/demandes/{reservation}/refuser', [GestionReservationController::class, 'refuser']);
        Route::post('/demandes/{reservation}/alternative', [GestionReservationController::class, 'alternative']);
        Route::post('/demandes/{reservation}/rendu', [GestionReservationController::class, 'rendu']);
        Route::post('/demandes/{reservation}/relancer', [GestionReservationController::class, 'relancer']);
    });

    /* Demandeur + Gestionnaire : consulter les équipements et le calendrier */
    Route::middleware('role:demandeur,gestionnaire')->group(function () {
        Route::get('/equipements', [EquipementController::class, 'index']);
        Route::get('/equipements/{equipement}', [EquipementController::class, 'show'])->whereNumber('equipement');
        Route::get('/equipements/{equipement}/disponibilite', [EquipementController::class, 'disponibilite'])->whereNumber('equipement');
        Route::get('/calendrier', [CalendrierController::class, 'index']);
        Route::get('/calendrier/evenements', [CalendrierController::class, 'evenements']);
    });

    /* Demandeur */
    Route::middleware('role:demandeur')->group(function () {
        Route::get('/mes-reservations', [ReservationController::class, 'mes']);
        Route::get('/reservations/nouvelle', [ReservationController::class, 'create']);
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/reservations/{reservation}/modifier', [ReservationController::class, 'edit']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::post('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);
        Route::post('/reservations/{reservation}/alternative', [ReservationController::class, 'alternative']);
    });

    /* Admin (séparé du gestionnaire : aucune généralisation) */
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/utilisateurs', [UtilisateurController::class, 'index']);
        Route::post('/utilisateurs', [UtilisateurController::class, 'store']);
        Route::put('/utilisateurs/{user}', [UtilisateurController::class, 'update']);
        Route::get('/rapports', [RapportController::class, 'index']);
        Route::get('/rapports/export', [RapportController::class, 'export']);
        Route::get('/parametres', [ParametreController::class, 'edit']);
        Route::put('/parametres', [ParametreController::class, 'update']);
    });
});
