<?php

use Illuminate\Support\Facades\Schedule;

// Passage automatique en « Terminée » après la date de fin (cahier des charges, workflow simplifié).
// En local avec Laragon : php artisan schedule:work
Schedule::command('reservations:cloturer')->everyMinute();
