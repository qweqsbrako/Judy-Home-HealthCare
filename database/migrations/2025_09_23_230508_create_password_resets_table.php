<?php

// database/migrations/2024_01_01_000001_create_password_resets_table.php

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
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->ipAddress('used_ip')->nullable();
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            
            // Indexes
            $table->index(['email', 'used_at']);
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};