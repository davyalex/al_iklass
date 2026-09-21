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
        Schema::table('parametres', function (Blueprint $table) {
            // Regroupe les paramètres par bloc dans l'écran Paramètres
            // (ex. "identite_application", "statut_journalier"...).
            $table->string('groupe')->default('general')->after('cle');
            $table->unsignedInteger('ordre')->default(0)->after('groupe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->dropColumn(['groupe', 'ordre']);
        });
    }
};
