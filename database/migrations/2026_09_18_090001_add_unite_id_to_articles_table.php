<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('unite_id')->nullable()->after('unite')->constrained('unites')->nullOnDelete();
        });

        $libelles = DB::table('articles')
            ->whereNotNull('unite')
            ->where('unite', '!=', '')
            ->distinct()
            ->pluck('unite');

        foreach ($libelles as $libelle) {
            $uniteId = DB::table('unites')->where('libelle', $libelle)->value('id');

            if (! $uniteId) {
                $uniteId = DB::table('unites')->insertGetId([
                    'libelle' => $libelle,
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('articles')->where('unite', $libelle)->update(['unite_id' => $uniteId]);
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('unite');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('unite')->nullable()->after('categorie_id');
        });

        DB::table('articles')
            ->join('unites', 'unites.id', '=', 'articles.unite_id')
            ->update(['articles.unite' => DB::raw('unites.libelle')]);

        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unite_id');
        });
    }
};
