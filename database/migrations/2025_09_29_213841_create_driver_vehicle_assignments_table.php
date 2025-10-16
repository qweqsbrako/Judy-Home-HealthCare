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
        Schema::create('driver_vehicle_assignments', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            
            // Assignment Details
            $table->datetime('assigned_at');
            $table->datetime('unassigned_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Assignment Information
            $table->foreignId('assigned_by')->constrained('users'); // Admin who made the assignment
            $table->foreignId('unassigned_by')->nullable()->constrained('users'); // Admin who removed assignment
            $table->text('assignment_notes')->nullable();
            $table->text('unassignment_reason')->nullable();
            
            // Current Status
            $table->enum('status', ['active', 'inactive', 'temporary'])->default('active');
            $table->datetime('effective_from')->nullable(); // When assignment becomes effective
            $table->datetime('effective_until')->nullable(); // When assignment expires (for temporary assignments)
            
            $table->timestamps();
            
            // Indexes
            $table->index(['driver_id', 'is_active']);
            $table->index(['vehicle_id', 'is_active']);
            $table->index(['assigned_at', 'unassigned_at']);
            
            // Ensure one active assignment per vehicle
            $table->unique(['vehicle_id'], 'unique_active_vehicle_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_vehicle_assignments');
    }
};