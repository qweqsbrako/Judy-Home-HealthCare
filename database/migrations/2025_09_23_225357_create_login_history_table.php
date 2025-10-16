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
        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('email');
            $table->ipAddress('ip_address');
            $table->text('user_agent');
            $table->boolean('successful');
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at');
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['successful', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_history');
    }
};