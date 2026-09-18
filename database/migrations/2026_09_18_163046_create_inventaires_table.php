<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaires', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignId('categorie_id')->nullable()->constrained('categories_article')->nullOnDelete();
            $table->date('date_inventaire');
            // brouillon, valide
            $table->string('statut')->default('brouillon');
            $table->text('commentaire')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('valide_par_id')->nullable()->constrained('users');
            $table->dateTime('valide_le')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaires');
    }
};
