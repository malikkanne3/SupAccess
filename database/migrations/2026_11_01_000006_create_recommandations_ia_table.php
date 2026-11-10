<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Classe Recommendation_IA : Type, Contenu, DécisionGestionnaire (l'IA recommande, le gestionnaire décide).
return new class extends Migration {
    public function up(): void
    {
        Schema::create('recommandations_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('contenu');
            $table->string('decision_gestionnaire', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommandations_ia');
    }
};
