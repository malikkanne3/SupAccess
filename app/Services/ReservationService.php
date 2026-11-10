<?php

namespace App\Services;

use App\Models\Equipement;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Règles métier du cahier des charges (section 5) + workflow complet d'une réservation. */
class ReservationService
{
    public function __construct(private RecommendationService $ia)
    {
    }

    public function limite(): int
    {
        return (int) Parametre::valeur(Parametre::LIMITE_RESERVATIONS, 2);
    }

    public function dureeMaxJours(): int
    {
        return (int) Parametre::valeur(Parametre::DUREE_MAX_JOURS, 7);
    }

    /* ------------------------------------------------------------------ Règles métier */

    public function verifierRegles(User $user, Equipement $e, int $quantite, Carbon $debut, Carbon $fin, ?int $ignorerId = null): void
    {
        $erreurs = [];

        if ($quantite < 1) {
            $erreurs['quantite'] = 'La quantité doit être au moins 1.';
        }
        if ($debut->gte($fin)) {                                           // date début < date fin
            $erreurs['date_fin'] = 'La date de fin doit être postérieure à la date de début.';
        }
        if ($debut->lt(now()->subMinutes(10))) {
            $erreurs['date_debut'] = 'La date de début ne peut pas être dans le passé.';
        }
        $max = $this->dureeMaxJours();
        if ($max > 0 && ! isset($erreurs['date_fin']) && $debut->diffInMinutes($fin) > $max * 1440) {
            $erreurs['date_fin'] = "La durée d'un emprunt est limitée à {$max} jour(s).";
        }

        if (! $erreurs) {
            if ($e->quantiteEnPanne() >= $e->quantite_totale) {              // en panne = non réservable
                $erreurs['equipement_id'] = 'Cet équipement est en panne : il ne peut pas être réservé.';
            } else {
                $dispo = $e->quantiteDisponible($debut, $fin, $ignorerId);   // pas de double réservation
                if ($quantite > $dispo) {
                    $erreurs['quantite'] = $dispo === 0
                        ? 'Cet équipement est déjà pris sur cette période.'
                        : "Seulement {$dispo} exemplaire(s) disponible(s) sur cette période.";
                }
            }
        }

        $limite = $this->limite();                                          // X réservations actives max
        if ($limite > 0 && $user->reservationsActives($ignorerId)->count() >= $limite) {
            $erreurs['equipement_id'] = "Vous avez atteint la limite de {$limite} réservation(s) active(s). Rendez un équipement ou annulez une demande.";
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }
    }

    /* ------------------------------------------------------------------ Côté demandeur */

    public function creer(User $user, array $d): Reservation
    {
        return DB::transaction(function () use ($user, $d) {
            $e = Equipement::lockForUpdate()->findOrFail($d['equipement_id']);
            $debut = Carbon::parse($d['date_debut']);
            $fin = Carbon::parse($d['date_fin']);
            $this->verifierRegles($user, $e, (int) $d['quantite'], $debut, $fin);

            $r = Reservation::create([
                'equipement_id' => $e->id,
                'user_id' => $user->id,
                'quantite' => (int) $d['quantite'],
                'date_debut' => $debut,
                'date_fin' => $fin,
                'motif' => $d['motif'],
                'statut' => Reservation::EN_ATTENTE,
            ]);
            $r->load('equipement');
            $this->ia->enregistrer($r);

            Notifier::gestionnaires(
                "Nouvelle demande : {$user->name}".($user->service ? " ({$user->service})" : '')." souhaite réserver {$e->nom} ×{$r->quantite} du {$debut->format('d/m/Y H:i')} au {$fin->format('d/m/Y H:i')}.",
                'info', '/demandes'
            );
            Notifier::envoyer($user, "Votre demande pour « {$e->nom} » est enregistrée. Elle sera examinée par le gestionnaire.", 'success', '/mes-reservations');

            return $r;
        });
    }

    public function modifier(User $user, Reservation $r, array $d): Reservation
    {
        if ($r->user_id !== $user->id || $r->statut !== Reservation::EN_ATTENTE) {
            throw new DomainException('Seule une demande en attente peut être modifiée par son auteur.');
        }
        return DB::transaction(function () use ($user, $r, $d) {
            $e = Equipement::lockForUpdate()->findOrFail($d['equipement_id']);
            $debut = Carbon::parse($d['date_debut']);
            $fin = Carbon::parse($d['date_fin']);
            $this->verifierRegles($user, $e, (int) $d['quantite'], $debut, $fin, $r->id);

            $r->modifier([
                'equipement_id' => $e->id,
                'quantite' => (int) $d['quantite'],
                'date_debut' => $debut,
                'date_fin' => $fin,
                'motif' => $d['motif'],
            ]);
            $r->load('equipement');
            $this->ia->enregistrer($r);
            Notifier::gestionnaires("{$user->name} a modifié sa demande pour {$e->nom}.", 'info', '/demandes', false);

            return $r;
        });
    }

    public function annuler(User $user, Reservation $r): void
    {
        $peut = $r->user_id === $user->id
            && ($r->statut === Reservation::EN_ATTENTE
                || $r->statut === Reservation::ALTERNATIVE
                || ($r->statut === Reservation::ACCEPTEE && $r->date_debut->isFuture()));
        if (! $peut) {
            throw new DomainException("Cette réservation ne peut plus être annulée (déjà commencée ou clôturée).");
        }
        $r->annuler('Annulée par le demandeur');
        Notifier::gestionnaires("{$user->name} a annulé sa réservation de {$r->equipement->nom}.", 'warning', '/demandes', false);
    }

    /** Option A : le demandeur confirme ou refuse l'alternative ; un refus annule la réservation. */
    public function repondreAlternative(User $user, Reservation $r, bool $accepte): void
    {
        if ($r->user_id !== $user->id || $r->statut !== Reservation::ALTERNATIVE || ! $r->equipement_propose_id) {
            throw new DomainException("Aucune alternative en attente de votre réponse.");
        }
        $alt = $r->equipementPropose;

        if (! $accepte) {
            $r->annuler("Alternative « {$alt->nom} » refusée par le demandeur");
            Notifier::gestionnaires("{$user->name} a refusé l'alternative « {$alt->nom} » : la réservation est annulée.", 'warning', '/demandes');
            return;
        }

        $dispo = $alt->quantiteDisponible($r->date_debut, $r->date_fin, $r->id);
        if ($dispo < $r->quantite) {
            throw ValidationException::withMessages(['alternative' => "« {$alt->nom} » n'est plus disponible sur cette période. Contactez le gestionnaire."]);
        }
        $r->update(['equipement_id' => $alt->id, 'equipement_propose_id' => null, 'statut' => Reservation::ACCEPTEE]);
        Notifier::gestionnaires("{$user->name} a accepté l'alternative « {$alt->nom} ».", 'success', '/demandes', false);
    }

    /* ------------------------------------------------------------------ Côté gestionnaire */

    public function accepter(User $g, Reservation $r): void
    {
        DB::transaction(function () use ($g, $r) {
            Equipement::lockForUpdate()->find($r->equipement_id);
            if ($r->date_fin->isPast()) {
                throw new DomainException('Cette demande est expirée : la période est déjà passée.');
            }
            if ($r->equipement->quantiteDisponible($r->date_debut, $r->date_fin, $r->id) < $r->quantite) {
                throw new DomainException("Impossible d'accepter : l'équipement n'est plus disponible en quantité suffisante sur la période.");
            }
            $r->accepter($g);
            $this->ia->decision($r, 'ACCEPTEE');
            Notifier::envoyer($r->demandeur, "Bonne nouvelle : votre réservation de {$r->equipement->nom} (du {$r->date_debut->format('d/m H:i')} au {$r->date_fin->format('d/m H:i')}) est acceptée.", 'success', '/mes-reservations');
        });
    }

    public function refuser(User $g, Reservation $r, string $raison): void
    {
        $etait = $r->statut;
        $r->refuser($g, $raison);
        $this->ia->decision($r, $etait === Reservation::EN_ATTENTE ? 'REFUSEE' : 'ANNULEE');
        $verbe = $etait === Reservation::EN_ATTENTE ? 'refusée' : 'annulée';
        Notifier::envoyer($r->demandeur, "Votre réservation de {$r->equipement->nom} a été {$verbe}. Motif : {$raison}", 'danger', '/mes-reservations');
    }

    public function proposerAlternative(User $g, Reservation $r, int $equipementId): void
    {
        if (! in_array($r->statut, [Reservation::EN_ATTENTE, Reservation::ACCEPTEE], true)) {
            throw new DomainException('Une alternative ne peut être proposée que pour une demande en attente ou acceptée.');
        }
        if ($equipementId === $r->equipement_id) {
            throw new DomainException("L'alternative doit être un autre équipement.");
        }
        $alt = Equipement::findOrFail($equipementId);
        if ($alt->quantiteDisponible($r->date_debut, $r->date_fin) < $r->quantite) {
            throw new DomainException("« {$alt->nom} » n'est pas disponible en quantité suffisante sur la période.");
        }
        $r->update(['statut' => Reservation::ALTERNATIVE, 'equipement_propose_id' => $alt->id, 'gestionnaire_id' => $g->id]);
        $this->ia->decision($r, 'ALTERNATIVE');
        Notifier::envoyer($r->demandeur, "Le gestionnaire vous propose « {$alt->nom} » à la place de {$r->equipement->nom}. Confirmez ou refusez depuis « Mes réservations ».", 'warning', '/mes-reservations');
    }

    public function marquerRendu(User $g, Reservation $r): void
    {
        $r->marquerRendu($g);
        Notifier::envoyer($r->demandeur, "Le retour de {$r->equipement->nom} est enregistré. Merci !", 'success', '/mes-reservations', false);
    }

    public function relancer(Reservation $r): void
    {
        if ($r->statut !== Reservation::TERMINEE) {
            throw new DomainException("Seul un équipement non rendu après la date de fin peut faire l'objet d'une relance.");
        }
        Notifier::envoyer($r->demandeur, "Rappel : {$r->equipement->nom} (×{$r->quantite}) devait être rendu le {$r->date_fin->format('d/m/Y à H:i')}. Merci de le rapporter au gestionnaire dès que possible.", 'danger', '/mes-reservations');
    }

    /* ------------------------------------------------------------------ Automatismes */

    /** Marquage automatique « Terminée » après la date de fin + mail de retour ; expire les demandes non traitées. */
    public function cloturerExpirees(): int
    {
        $n = 0;
        Reservation::with(['equipement', 'demandeur'])->where('statut', Reservation::ACCEPTEE)->where('date_fin', '<=', now())->get()
            ->each(function (Reservation $r) use (&$n) {
                $r->terminer();
                Notifier::envoyer($r->demandeur, "Votre réservation de {$r->equipement->nom} est terminée. Merci de retourner l'équipement au gestionnaire, qui le marquera comme rendu.", 'warning', '/mes-reservations');
                $n++;
            });

        Reservation::with(['equipement', 'demandeur'])->whereIn('statut', [Reservation::EN_ATTENTE, Reservation::ALTERNATIVE])->where('date_fin', '<=', now())->get()
            ->each(function (Reservation $r) {
                $r->annuler('Demande expirée (non traitée avant la fin de la période)');
                Notifier::envoyer($r->demandeur, "Votre demande pour {$r->equipement->nom} a expiré faute de traitement avant la fin de la période.", 'info', '/mes-reservations', false);
            });

        return $n;
    }
}
