<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table unique programmation + historique : réaliser une opération clôture
        // la ligne courante (statut, date_realisation) et en crée une nouvelle
        // pour le prochain cycle, dans la même transaction (OperationProgrammeeService).
        // L'historique d'un véhicule est simplement l'ensemble de ses lignes.
        Schema::create('operations_programmees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules');
            $table->string('vehicule_code'); // snapshot
            $table->foreignId('type_operation_id')->constrained('types_operation');
            $table->string('type_operation_code'); // snapshot
            $table->string('type_operation_libelle'); // snapshot
            $table->date('date_echeance');
            // Nombre de jours avant date_echeance à partir duquel le badge passe en alerte (jaune).
            $table->unsignedInteger('rappel_jours');
            // Copié du référentiel à la création : permet un override par véhicule/ligne.
            $table->unsignedInteger('periodicite_jours')->nullable();
            $table->string('statut')->default('planifiee'); // planifiee, realisee
            $table->date('date_realisation')->nullable();
            $table->text('commentaire')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('realise_par_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_programmees');
    }
};
