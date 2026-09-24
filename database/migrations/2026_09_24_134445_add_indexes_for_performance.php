<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financements', function (Blueprint $table) {
            $table->index('statut');
        });

        Schema::table('mouvements_caisse', function (Blueprint $table) {
            $table->index(['caisse_id', 'sens']);
        });

        Schema::table('versements', function (Blueprint $table) {
            $table->index('date_versement');
        });
    }

    public function down(): void
    {
        Schema::table('financements', function (Blueprint $table) {
            $table->dropIndex(['statut']);
        });

        Schema::table('mouvements_caisse', function (Blueprint $table) {
            $table->dropIndex(['caisse_id', 'sens']);
        });

        Schema::table('versements', function (Blueprint $table) {
            $table->dropIndex(['date_versement']);
        });
    }
};
