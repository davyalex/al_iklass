<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_sortie_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_sortie_id')->constrained('demandes_sortie')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles');
            $table->string('article_reference'); // snapshot
            $table->string('article_nom'); // snapshot
            $table->integer('quantite');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_sortie_lignes');
    }
};
