<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('name');
            $table->string('telephone', 10)->unique()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('is_active');
            $table->timestamp('locked_at')->nullable()->after('failed_login_attempts');
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['username', 'telephone', 'is_active', 'failed_login_attempts', 'locked_at']);
        });
    }
};
