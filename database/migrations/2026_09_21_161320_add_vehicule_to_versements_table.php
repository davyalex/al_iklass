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
        Schema::table('versements', function (Blueprint $table) {
            // Facultatif : un gestionnaire verse en général un montant global
            // pour tous ses véhicules en circulation, mais on peut préciser
            // le véhicule concerné quand c'est pertinent.
            $table->foreignId('vehicule_id')->nullable()->after('gestionnaire_nom')->constrained('vehicules')->nullOnDelete();
            $table->string('vehicule_code')->nullable()->after('vehicule_id'); // snapshot
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('versements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicule_id');
            $table->dropColumn('vehicule_code');
        });
    }
};
