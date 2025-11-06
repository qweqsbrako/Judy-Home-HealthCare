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
            // Temporary columns for 2FA setup verification
            $table->text('two_factor_temp_token')->nullable()->after('two_factor_method');
            $table->string('two_factor_temp_method')->nullable()->after('two_factor_temp_token');
            $table->timestamp('two_factor_temp_expires')->nullable()->after('two_factor_temp_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_temp_token',
                'two_factor_temp_method',
                'two_factor_temp_expires'
            ]);
        });
    }
};