<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();                 // Demandeur « passe »
            $table->foreignId('gestionnaire_id')->nullable()->constrained('users')->nullOnDelete(); // Gestionnaire « traite »
            $table->foreignId('equipement_propose_id')->nullable()->constrained('equipements')->nullOnDelete();
            $table->unsignedInteger('quantite')->default(1);
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->text('motif');
            // EN_ATTENTE | ACCEPTEE | REFUSEE | TERMINEE | RENDUE | ANNULEE | ALTERNATIVE
            $table->string('statut', 20)->default('EN_ATTENTE');
            $table->string('motif_refus')->nullable();
            $table->timestamp('rendu_at')->nullable();
            $table->timestamps();

            $table->index(['equipement_id', 'statut']);
            $table->index(['user_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
