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
        Schema::create('patient_feedback', function (Blueprint $table) {
            $table->id();
            
            // Core relationships
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('nurse_id')->constrained('users')->onDelete('cascade');
            
            // Simple feedback content
            $table->integer('rating')->comment('1-5 star rating');
            $table->text('feedback_text');
            $table->boolean('would_recommend')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['patient_id', 'created_at']);
            $table->index(['nurse_id', 'rating']);
            $table->index(['nurse_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_feedback');
    }
};