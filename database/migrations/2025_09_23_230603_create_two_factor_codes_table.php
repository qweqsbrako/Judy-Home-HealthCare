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
        Schema::create('two_factor_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code'); // Hashed code
            $table->enum('method', ['email', 'sms', 'voice'])->default('email');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->ipAddress('verified_ip')->nullable();
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->string('login_attempt_id')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'used_at']);
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_codes');
    }
};