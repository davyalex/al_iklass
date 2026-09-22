<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_sortie', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignId('vehicule_id')->constrained('vehicules');
            $table->string('vehicule_code'); // snapshot
            $table->text('motif')->nullable();
            $table->date('date_demande');
            // en_attente, validee, rejetee
            $table->string('statut')->default('en_attente');
            $table->foreignId('demandeur_id')->constrained('users');
            $table->string('demandeur_nom'); // snapshot
            $table->foreignId('traite_par_id')->nullable()->constrained('users');
            $table->string('traite_par_nom')->nullable(); // snapshot
            $table->text('commentaire_traitement')->nullable();
            $table->foreignId('sortie_id')->nullable()->constrained('sorties_stock');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_sortie');
    }
};
