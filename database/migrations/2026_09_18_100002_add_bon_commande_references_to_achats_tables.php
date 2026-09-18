<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achats', function (Blueprint $table) {
            $table->foreignId('bon_commande_id')->nullable()->after('fournisseur_nom')->constrained('bons_commande');
        });

        Schema::table('achat_lignes', function (Blueprint $table) {
            // Ligne du bon de commande que cette ligne de réception vient honorer (nullable :
            // un achat direct, sans bon de commande préalable, reste possible).
            $table->foreignId('bon_commande_ligne_id')->nullable()->after('article_id')->constrained('bon_commande_lignes');
        });
    }

    public function down(): void
    {
        Schema::table('achat_lignes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bon_commande_ligne_id');
        });

        Schema::table('achats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bon_commande_id');
        });
    }
};
