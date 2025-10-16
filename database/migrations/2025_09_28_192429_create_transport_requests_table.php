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
        Schema::create('transport_requests', function (Blueprint $table) {
            $table->id();
            
            // Request Information
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('requested_by_id')->constrained('users'); // Can be patient or nurse
            $table->enum('transport_type', ['ambulance', 'regular']);
            $table->enum('priority', ['emergency', 'urgent', 'routine']);
            $table->datetime('scheduled_time');
            
            // Location Information
            $table->string('pickup_location');
            $table->text('pickup_address');
            $table->string('destination_location');
            $table->text('destination_address');
            
            // Transport Details
            $table->text('reason');
            $table->text('special_requirements')->nullable();
            $table->string('contact_person')->nullable();

            // Assignment and Status
            $table->foreignId('driver_id')->nullable()->constrained('drivers');
            $table->enum('status', ['requested', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('requested');
            
            // Timing
            $table->datetime('actual_pickup_time')->nullable();
            $table->datetime('actual_arrival_time')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            
            // Costs
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            
            // Feedback
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->text('feedback')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable(); // GPS coordinates, etc.
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['patient_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index(['scheduled_time', 'status']);
            $table->index(['transport_type', 'priority']);
            $table->index('requested_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_requests');
    }
};