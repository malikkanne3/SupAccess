<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Classe User du diagramme : Role + Service (Demandeur, Gestionnaire, Admin héritent de User).
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('demandeur')->after('password');
            $table->string('service')->nullable()->after('role');
            $table->boolean('actif')->default(true)->after('service');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'service', 'actif']);
        });
    }
};
