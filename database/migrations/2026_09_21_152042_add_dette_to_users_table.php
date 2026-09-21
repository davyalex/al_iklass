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
        Schema::table('users', function (Blueprint $table) {
            // Dette cumulée d'un gestionnaire : solde non versé reporté des
            // jours précédents (cf. CONTEXTE.md §4). Ne bouge pas tant que le
            // moteur de bascule quotidienne en dette n'est pas construit
            // (Sprint « Recette, versement & caisses ») ; pour l'instant,
            // seul un ajustement manuel par un administrateur peut la modifier.
            $table->decimal('dette', 12, 2)->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dette');
        });
    }
};
