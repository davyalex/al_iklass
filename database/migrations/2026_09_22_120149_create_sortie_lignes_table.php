<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sortie_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sortie_id')->constrained('sorties_stock')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles');
            $table->string('article_reference'); // snapshot
            $table->string('article_nom'); // snapshot
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 12, 2); // valorisation, au prix d'achat
            $table->decimal('prix_vente', 12, 2)->nullable(); // sortie externe uniquement
            $table->decimal('montant', 12, 2); // quantite * (prix_vente ?? prix_unitaire)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sortie_lignes');
    }
};
