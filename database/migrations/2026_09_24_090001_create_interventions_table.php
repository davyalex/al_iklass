<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules');
            $table->string('vehicule_code'); // snapshot
            $table->foreignId('type_panne_id')->nullable()->constrained('types_panne');
            $table->string('type_panne_libelle')->nullable(); // snapshot
            $table->text('description');
            $table->string('statut')->default('en_cours'); // en_cours, terminee
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->text('rapport')->nullable();
            $table->foreignId('declaree_par_id')->constrained('users');
            $table->foreignId('cloturee_par_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
