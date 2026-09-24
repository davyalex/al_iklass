<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignId('preteur_id')->constrained('preteurs');
            $table->string('preteur_nom'); // snapshot
            $table->date('date_financement');
            $table->decimal('montant_total', 12, 2);
            $table->decimal('montant_rembourse', 12, 2)->default(0);
            $table->decimal('montant_restant', 12, 2);
            $table->string('statut'); // en_cours, solde
            $table->text('commentaire')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financements');
    }
};
