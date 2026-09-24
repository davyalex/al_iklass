<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursements_financement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financement_id')->constrained('financements');
            $table->string('preteur_nom'); // snapshot
            $table->date('date_remboursement');
            $table->decimal('montant', 12, 2);
            $table->foreignId('mode_paiement_id')->nullable()->constrained('modes_paiement');
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements_financement');
    }
};
