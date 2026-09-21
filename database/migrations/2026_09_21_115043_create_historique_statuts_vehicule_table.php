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
        Schema::create('historique_statuts_vehicule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
            $table->string('vehicule_code');
            $table->foreignId('ancien_statut_id')->nullable()->constrained('statuts_vehicule')->nullOnDelete();
            $table->string('ancien_statut_code')->nullable();
            $table->string('ancien_statut_libelle')->nullable();
            $table->foreignId('nouveau_statut_id')->nullable()->constrained('statuts_vehicule')->nullOnDelete();
            $table->string('nouveau_statut_code')->nullable();
            $table->string('nouveau_statut_libelle')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('historique_statuts_vehicule');
    }
};
