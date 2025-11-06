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
            // Columns for 2FA login verification
            $table->text('login_verification_token')->nullable()->after('two_factor_temp_expires');
            $table->timestamp('login_verification_expires')->nullable()->after('login_verification_token');
            
            // Columns for biometric session during login
            $table->text('login_session_token')->nullable()->after('login_verification_expires');
            $table->timestamp('login_session_expires')->nullable()->after('login_session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_verification_token',
                'login_verification_expires',
                'login_session_token',
                'login_session_expires'
            ]);
        });
    }
};