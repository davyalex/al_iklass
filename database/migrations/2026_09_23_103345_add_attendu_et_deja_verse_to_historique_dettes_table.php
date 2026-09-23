<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historique_dettes', function (Blueprint $table) {
            // Uniquement renseignés pour une bascule : recette attendue et
            // montant déjà versé ce jour-là (date_reference), snapshotés au
            // moment du calcul — évite de devoir reconstituer ces chiffres
            // après coup pour l'affichage du détail par gestionnaire.
            $table->decimal('attendu', 12, 2)->nullable()->after('date_reference');
            $table->decimal('deja_verse', 12, 2)->nullable()->after('attendu');
        });
    }

    public function down(): void
    {
        Schema::table('historique_dettes', function (Blueprint $table) {
            $table->dropColumn(['attendu', 'deja_verse']);
        });
    }
};
