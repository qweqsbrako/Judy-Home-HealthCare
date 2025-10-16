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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('token_id')->nullable(); // Sanctum token ID
            $table->ipAddress('ip_address');
            $table->text('user_agent');
            $table->string('device_name')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('logged_out_at')->nullable();
            $table->timestamp('last_activity');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'logged_out_at']);
            $table->index('token_id');
            $table->index('last_activity');
            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};