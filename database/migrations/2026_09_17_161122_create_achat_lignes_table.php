<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achat_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achat_id')->constrained('achats')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles');
            $table->string('article_reference'); // snapshot
            $table->string('article_nom'); // snapshot
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('montant', 12, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achat_lignes');
    }
};
