<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\Panne;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Jeu de tests du cahier des charges : réservation bloquée en cas de conflit horaire + autres règles métier. */
class ReservationRulesTest extends TestCase
{
    use RefreshDatabase;

    private ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReservationService::class);
    }

    private function user(string $email = 'a@test.sn', string $role = 'demandeur'): User
    {
        return User::create(['name' => 'Test '.$email, 'email' => $email, 'password' => 'secret123', 'role' => $role, 'actif' => true]);
    }

    private function equipement(int $qte = 1): Equipement
    {
        return Equipement::create(['nom' => 'Vidéoprojecteur', 'quantite_totale' => $qte, 'categorie_id' => Categorie::create(['nom' => 'Audiovisuel'])->id]);
    }

    private function demande(User $u, Equipement $e, string $debut, string $fin, int $qte = 1): Reservation
    {
        return $this->service->creer($u, ['equipement_id' => $e->id, 'quantite' => $qte, 'date_debut' => $debut, 'date_fin' => $fin, 'motif' => 'Test']);
    }

    private function bloquer(User $u, Equipement $e, string $debut, string $fin, int $qte = 1): Reservation
    {
        return Reservation::create(['equipement_id' => $e->id, 'user_id' => $u->id, 'quantite' => $qte, 'date_debut' => $debut, 'date_fin' => $fin, 'motif' => 'x', 'statut' => Reservation::ACCEPTEE]);
    }

    public function test_reservation_bloquee_si_conflit_horaire(): void
    {
        $e = $this->equipement(1);
        $this->bloquer($this->user('a@test.sn'), $e, now()->addDay()->setTime(9, 0), now()->addDay()->setTime(12, 0));

        $this->expectException(ValidationException::class);
        $this->demande($this->user('b@test.sn'), $e, now()->addDay()->setTime(10, 0)->toDateTimeString(), now()->addDay()->setTime(11, 0)->toDateTimeString());
    }

    public function test_reservation_possible_hors_de_la_plage_deja_prise(): void
    {
        $e = $this->equipement(1);
        $this->bloquer($this->user('a@test.sn'), $e, now()->addDay()->setTime(9, 0), now()->addDay()->setTime(12, 0));

        $r = $this->demande($this->user('b@test.sn'), $e, now()->addDay()->setTime(12, 0)->toDateTimeString(), now()->addDay()->setTime(14, 0)->toDateTimeString());

        $this->assertSame(Reservation::EN_ATTENTE, $r->statut);
    }

    public function test_stock_partiel_pour_des_exemplaires_identiques(): void
    {
        $e = $this->equipement(10);
        $this->bloquer($this->user('a@test.sn'), $e, now()->addDay()->setTime(9, 0), now()->addDay()->setTime(17, 0), 8);
        $debut = now()->addDay()->setTime(10, 0)->toDateTimeString();
        $fin = now()->addDay()->setTime(12, 0)->toDateTimeString();

        $this->assertSame(2, $e->quantiteDisponible(now()->addDay()->setTime(10, 0), now()->addDay()->setTime(12, 0)));
        $this->assertSame(Reservation::EN_ATTENTE, $this->demande($this->user('b@test.sn'), $e, $debut, $fin, 2)->statut);

        $this->expectException(ValidationException::class);
        $this->demande($this->user('c@test.sn'), $e, $debut, $fin, 3);
    }

    public function test_la_date_de_debut_doit_preceder_la_date_de_fin(): void
    {
        $e = $this->equipement();
        $this->expectException(ValidationException::class);
        $this->demande($this->user(), $e, now()->addDays(2)->toDateTimeString(), now()->addDay()->toDateTimeString());
    }

    public function test_un_equipement_en_panne_est_non_reservable(): void
    {
        $e = $this->equipement(2);
        Panne::create(['equipement_id' => $e->id, 'quantite' => 2, 'description' => 'HS']);

        $this->expectException(ValidationException::class);
        $this->demande($this->user(), $e, now()->addDay()->toDateTimeString(), now()->addDays(2)->toDateTimeString());
    }

    public function test_limite_de_reservations_actives(): void
    {
        Parametre::definir(Parametre::LIMITE_RESERVATIONS, 2);
        $u = $this->user();
        $e = $this->equipement(5);
        $this->demande($u, $e, now()->addDay()->toDateTimeString(), now()->addDays(2)->toDateTimeString());
        $this->demande($u, $e, now()->addDays(3)->toDateTimeString(), now()->addDays(4)->toDateTimeString());

        $this->expectException(ValidationException::class);
        $this->demande($u, $e, now()->addDays(5)->toDateTimeString(), now()->addDays(6)->toDateTimeString());
    }

    public function test_un_equipement_non_rendu_compte_dans_la_limite(): void
    {
        Parametre::definir(Parametre::LIMITE_RESERVATIONS, 1);
        $u = $this->user();
        $e = $this->equipement(5);
        Reservation::create(['equipement_id' => $e->id, 'user_id' => $u->id, 'quantite' => 1, 'date_debut' => now()->subDays(3), 'date_fin' => now()->subDay(), 'motif' => 'x', 'statut' => Reservation::TERMINEE]);

        $this->expectException(ValidationException::class);
        $this->demande($u, $e, now()->addDay()->toDateTimeString(), now()->addDays(2)->toDateTimeString());
    }

    public function test_duree_maximale_d_un_emprunt(): void
    {
        Parametre::definir(Parametre::DUREE_MAX_JOURS, 7);
        $this->expectException(ValidationException::class);
        $this->demande($this->user(), $this->equipement(), now()->addDay()->toDateTimeString(), now()->addDays(20)->toDateTimeString());
    }

    public function test_cloture_automatique_apres_la_date_de_fin(): void
    {
        $u = $this->user();
        $e = $this->equipement();
        $r = Reservation::create(['equipement_id' => $e->id, 'user_id' => $u->id, 'quantite' => 1, 'date_debut' => now()->subDays(2), 'date_fin' => now()->subHour(), 'motif' => 'x', 'statut' => Reservation::ACCEPTEE]);

        $this->assertSame(1, $this->service->cloturerExpirees());
        $this->assertSame(Reservation::TERMINEE, $r->fresh()->statut);
    }

    public function test_separation_des_roles(): void
    {
        $admin = $this->user('admin@test.sn', 'admin');
        $this->actingAs($admin)->get('/demandes')->assertRedirect('/tableau-de-bord');
        $this->actingAs($admin)->get('/equipements')->assertRedirect('/tableau-de-bord');

        $demandeur = $this->user('d@test.sn');
        $this->actingAs($demandeur)->get('/admin/utilisateurs')->assertRedirect('/tableau-de-bord');
    }
}
