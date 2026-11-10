<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\Panne;
use App\Models\Parametre;
use App\Models\Recommandation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Parametre::definir(Parametre::LIMITE_RESERVATIONS, 2);
        Parametre::definir(Parametre::DUREE_MAX_JOURS, 7);

        /* Comptes de démonstration (mot de passe : Password123) */
        $mdp = Hash::make('Password123');
        $admin = User::create(['name' => 'Direction SUPDECO', 'email' => 'admin@supdeco.sn', 'password' => $mdp, 'role' => 'admin', 'service' => 'Direction']);
        $gest = User::create(['name' => 'Ibrahima Sarr', 'email' => 'gestionnaire@supdeco.sn', 'password' => $mdp, 'role' => 'gestionnaire', 'service' => 'Service logistique']);
        $awa = User::create(['name' => 'Awa Diop', 'email' => 'awa@supdeco.sn', 'password' => $mdp, 'role' => 'demandeur', 'service' => 'Licence 3 Génie Logiciel']);
        $moussa = User::create(['name' => 'Moussa Ndiaye', 'email' => 'moussa@supdeco.sn', 'password' => $mdp, 'role' => 'demandeur', 'service' => 'Licence 2 Réseaux']);
        $fatou = User::create(['name' => 'Fatou Sow', 'email' => 'fatou@supdeco.sn', 'password' => $mdp, 'role' => 'demandeur', 'service' => 'Club audiovisuel']);

        /* Catégories */
        $cat = [];
        foreach ([
            'Audiovisuel' => 'Projection, sonorisation et prise de son',
            'Informatique' => 'Ordinateurs portables et postes de travail',
            'Réseau' => 'Matériel réseau pour les travaux pratiques',
            'Salles' => 'Salles de réunion et de travail',
            'Photo et vidéo' => 'Captation et production de contenus',
        ] as $nom => $desc) {
            $cat[$nom] = Categorie::create(['nom' => $nom, 'description' => $desc]);
        }

        /* Équipements : un équipement = un modèle précis ; quantité seulement pour des exemplaires identiques */
        $eq = [];
        foreach ([
            ['Vidéoprojecteur Epson EB-X49', 'Audiovisuel', 3, 'Projecteur XGA 3600 lumens, câble HDMI et télécommande fournis.'],
            ['Micro-cravate Rode Wireless GO', 'Audiovisuel', 4, 'Micro sans fil compact, idéal pour les présentations et les tournages.'],
            ['PC portable HP ProBook 450', 'Informatique', 10, 'Core i5, 16 Go de RAM, 512 Go SSD. Exemplaires strictement identiques.'],
            ['PC portable Dell Latitude 5420', 'Informatique', 5, 'Core i7, 16 Go de RAM, 512 Go SSD, Windows 11 Pro.'],
            ['Switch Cisco Catalyst 2960', 'Réseau', 4, 'Switch 24 ports Fast Ethernet pour les travaux pratiques de routage et commutation.'],
            ['Salle de réunion A', 'Salles', 1, 'Salle de 12 places équipée d\'un écran et d\'un tableau blanc.'],
            ['Salle de réunion B', 'Salles', 1, 'Salle de 8 places, visioconférence disponible.'],
            ['Caméra Canon EOS 250D', 'Photo et vidéo', 2, 'Reflex 24 Mpx avec objectif 18-55 mm et deux batteries.'],
        ] as [$nom, $c, $qte, $desc]) {
            $eq[$nom] = Equipement::create(['nom' => $nom, 'categorie_id' => $cat[$c]->id, 'quantite_totale' => $qte, 'description' => $desc]);
        }

        $h = fn (int $jours, int $heure) => now()->addDays($jours)->setTime($heure, 0);

        /* Pannes : une en cours, une résolue */
        Panne::create(['equipement_id' => $eq['Switch Cisco Catalyst 2960']->id, 'quantite' => 1, 'description' => 'Port 12 défectueux, le switch redémarre en boucle.']);
        Panne::create(['equipement_id' => $eq['Vidéoprojecteur Epson EB-X49']->id, 'quantite' => 1, 'description' => 'Lampe grillée, remplacée.', 'resolue_at' => now()->subDays(5)]);

        /* Réservations d'exemple couvrant le workflow */
        $r = fn (array $a) => Reservation::create($a);

        $r(['equipement_id' => $eq['Caméra Canon EOS 250D']->id, 'user_id' => $fatou->id, 'gestionnaire_id' => $gest->id, 'quantite' => 1,
            'date_debut' => $h(-4, 9), 'date_fin' => $h(-1, 17), 'motif' => 'Tournage du reportage sur la journée portes ouvertes.', 'statut' => Reservation::TERMINEE]);
        $r(['equipement_id' => $eq['Micro-cravate Rode Wireless GO']->id, 'user_id' => $moussa->id, 'gestionnaire_id' => $gest->id, 'quantite' => 2,
            'date_debut' => $h(-12, 8), 'date_fin' => $h(-10, 18), 'motif' => 'Enregistrement du podcast du club.', 'statut' => Reservation::RENDUE, 'rendu_at' => $h(-10, 18)->addHours(2)]);
        $r(['equipement_id' => $eq['Salle de réunion B']->id, 'user_id' => $moussa->id, 'gestionnaire_id' => $gest->id, 'quantite' => 1,
            'date_debut' => $h(-8, 14), 'date_fin' => $h(-8, 17), 'motif' => 'Révisions en groupe.', 'statut' => Reservation::REFUSEE, 'motif_refus' => 'Salle déjà bloquée pour une réunion pédagogique.']);

        $r(['equipement_id' => $eq['Vidéoprojecteur Epson EB-X49']->id, 'user_id' => $awa->id, 'gestionnaire_id' => $gest->id, 'quantite' => 1,
            'date_debut' => $h(1, 9), 'date_fin' => $h(2, 17), 'motif' => 'Soutenance blanche du projet de fin d\'année.', 'statut' => Reservation::ACCEPTEE]);
        $r(['equipement_id' => $eq['Salle de réunion A']->id, 'user_id' => $fatou->id, 'gestionnaire_id' => $gest->id, 'quantite' => 1,
            'date_debut' => $h(3, 10), 'date_fin' => $h(3, 16), 'motif' => 'Réunion de préparation du forum étudiant.', 'statut' => Reservation::ACCEPTEE]);

        $enAttente = [
            $r(['equipement_id' => $eq['PC portable HP ProBook 450']->id, 'user_id' => $awa->id, 'quantite' => 2,
                'date_debut' => $h(3, 8), 'date_fin' => $h(5, 18), 'motif' => 'Atelier Laravel : développement en binôme.', 'statut' => Reservation::EN_ATTENTE]),
            $r(['equipement_id' => $eq['Salle de réunion A']->id, 'user_id' => $moussa->id, 'quantite' => 1,
                'date_debut' => $h(3, 11), 'date_fin' => $h(3, 15), 'motif' => 'Présentation du projet réseau devant le jury.', 'statut' => Reservation::EN_ATTENTE]),
        ];

        // Recommandations IA (le gestionnaire décide)
        $ia = app(\App\Services\RecommendationService::class);
        foreach ($enAttente as $res) {
            $ia->enregistrer($res->load('equipement'));
        }
    }
}
