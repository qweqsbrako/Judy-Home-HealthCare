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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('driver_id')->unique(); // Custom driver ID
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('avatar')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Performance Metrics
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('total_trips')->default(0);
            $table->integer('completed_trips')->default(0);
            $table->integer('cancelled_trips')->default(0);
            
            // Suspension Information
            $table->boolean('is_suspended')->default(false);
            $table->datetime('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users');
            
            // Additional Information
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'is_suspended']);
            $table->index('average_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};