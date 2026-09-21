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
        Schema::table('vehicules', function (Blueprint $table) {
            $table->string('marque', 50)->nullable();
            $table->string('modele', 50)->nullable();
            $table->string('immatriculation', 20)->nullable()->unique();
            $table->date('date_mise_circulation')->nullable();
            $table->foreignId('statut_id')->nullable()->constrained('statuts_vehicule')->nullOnDelete();
            $table->string('chauffeur_nom')->nullable();
            $table->string('chauffeur_telephone', 20)->nullable();
            $table->decimal('recette_journaliere', 12, 2)->default(0);
            $table->foreignId('gestionnaire_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('statut_id');
            $table->dropConstrainedForeignId('gestionnaire_id');
            $table->dropColumn([
                'marque',
                'modele',
                'immatriculation',
                'date_mise_circulation',
                'chauffeur_nom',
                'chauffeur_telephone',
                'recette_journaliere',
            ]);
        });
    }
};
