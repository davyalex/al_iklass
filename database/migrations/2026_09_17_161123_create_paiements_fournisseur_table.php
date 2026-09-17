<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements_fournisseur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achat_id')->constrained('achats');
            $table->date('date_paiement');
            $table->decimal('montant', 12, 2);
            $table->foreignId('mode_paiement_id')->constrained('modes_paiement');
            $table->string('reference')->nullable();
            $table->string('fournisseur_nom'); // snapshot
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_fournisseur');
    }
};
