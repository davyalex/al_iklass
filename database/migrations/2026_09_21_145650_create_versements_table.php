<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestionnaire_id')->constrained('users');
            $table->string('gestionnaire_nom'); // snapshot
            $table->decimal('montant', 12, 2);
            $table->foreignId('mode_paiement_id')->constrained('modes_paiement');
            $table->date('date_versement');
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->string('commentaire')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versements');
    }
};
