<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

class CloturerReservations extends Command
{
    protected $signature = 'reservations:cloturer';

    protected $description = 'Passe en « Terminée » les réservations dont la date de fin est dépassée et prévient les demandeurs';

    public function handle(ReservationService $service): int
    {
        $n = $service->cloturerExpirees();
        $this->info("{$n} réservation(s) passée(s) en « Terminée ».");

        return self::SUCCESS;
    }
}
