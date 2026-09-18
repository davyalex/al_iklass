<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bon_commande_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_commande_id')->constrained('bons_commande')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles');
            $table->string('article_reference'); // snapshot
            $table->string('article_nom'); // snapshot
            $table->integer('quantite_commandee');
            $table->integer('quantite_recue')->default(0);
            $table->decimal('prix_unitaire_estime', 12, 2);
            $table->decimal('montant_estime', 12, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bon_commande_lignes');
    }
};
