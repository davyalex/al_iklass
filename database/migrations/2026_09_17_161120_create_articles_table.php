<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('categorie_id')->nullable()->constrained('categories_article')->nullOnDelete();
            $table->string('unite')->nullable();
            $table->integer('quantite_stock')->default(0);
            $table->decimal('prix_achat', 12, 2)->default(0);
            $table->decimal('prix_vente', 12, 2)->nullable();
            $table->integer('seuil_alerte')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
