<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preteurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('type_preteur_id')->constrained('types_preteur');
            $table->string('type_preteur_code'); // snapshot
            $table->string('type_preteur_libelle'); // snapshot
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preteurs');
    }
};
