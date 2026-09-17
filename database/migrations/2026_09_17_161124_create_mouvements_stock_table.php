<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles');
            $table->string('article_reference'); // snapshot
            $table->string('article_nom'); // snapshot
            $table->string('type'); // entree, sortie
            $table->string('nature')->nullable(); // interne, externe (sortie uniquement)
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 12, 2); // valorisation (prix d'achat)
            $table->decimal('prix_vente', 12, 2)->nullable(); // sortie externe uniquement
            $table->string('motif')->nullable();
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules');
            $table->string('vehicule_code')->nullable(); // snapshot
            $table->string('vehicule_externe')->nullable();
            $table->string('acheteur')->nullable();
            $table->foreignId('achat_id')->nullable()->constrained('achats');
            // Pas de contrainte FK : la table "interventions" n'existe pas encore (Sprint 6).
            $table->unsignedBigInteger('intervention_id')->nullable();
            $table->foreignId('caisse_mouvement_id')->nullable()->constrained('mouvements_caisse');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('date_mouvement');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};
