<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_caisse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses');
            $table->string('sens'); // entree, sortie
            $table->decimal('montant', 12, 2);
            $table->foreignId('mode_paiement_id')->nullable()->constrained('modes_paiement');
            $table->string('reference')->nullable();
            $table->string('motif')->nullable();
            $table->nullableMorphs('origine');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('date_mouvement');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_caisse');
    }
};
