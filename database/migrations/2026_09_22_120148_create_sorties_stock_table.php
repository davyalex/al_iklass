<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorties_stock', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('nature'); // interne, externe
            $table->date('date_sortie');
            $table->string('motif');
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules');
            $table->string('vehicule_code')->nullable(); // snapshot
            $table->string('vehicule_externe')->nullable();
            $table->string('acheteur')->nullable();
            $table->decimal('montant_total', 12, 2)->default(0); // externe : Σ vente ; interne : Σ valorisation
            $table->foreignId('caisse_mouvement_id')->nullable()->constrained('mouvements_caisse');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties_stock');
    }
};
