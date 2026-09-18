<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaire_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaire_id')->constrained('inventaires')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles');
            $table->string('article_reference'); // snapshot
            $table->string('article_nom'); // snapshot
            $table->integer('quantite_theorique');
            $table->integer('quantite_comptee')->nullable();
            $table->decimal('prix_achat_unitaire', 12, 2); // snapshot, valorisation de l'ecart
            $table->string('commentaire')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaire_lignes');
    }
};
