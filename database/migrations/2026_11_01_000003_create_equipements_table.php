<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Un équipement = un modèle précis ; la quantité ne sert que pour des exemplaires strictement identiques.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('quantite_totale')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
