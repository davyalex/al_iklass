<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_dettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestionnaire_id')->constrained('users');
            $table->string('gestionnaire_nom'); // snapshot
            // bascule : ajout automatique du reste a verser de la veille ; annulation : remise partielle/totale par un admin.
            $table->string('type');
            $table->decimal('montant', 12, 2);
            $table->decimal('dette_avant', 12, 2);
            $table->decimal('dette_apres', 12, 2);
            $table->text('motif')->nullable();
            $table->date('date_reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_dettes');
    }
};
