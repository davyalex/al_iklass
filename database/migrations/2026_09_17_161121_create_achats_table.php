<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achats', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs');
            $table->string('fournisseur_nom'); // snapshot
            $table->date('date_achat');
            $table->decimal('montant_total', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->decimal('montant_restant', 12, 2);
            $table->string('statut_paiement'); // comptant, partiel, credit
            $table->text('commentaire')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achats');
    }
};
