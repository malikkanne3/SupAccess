<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Classe Notification : Message, Type (un User « reçoit » 0..* notifications).
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('info'); // info | success | warning | danger
            $table->text('message');
            $table->string('lien')->nullable();
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lu_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
