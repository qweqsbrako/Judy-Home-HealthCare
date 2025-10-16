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
        Schema::create('care_assignments', function (Blueprint $table) {
            $table->id();
            
            // Core Assignment Relationships
            $table->foreignId('care_plan_id')->constrained('care_plans')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('primary_nurse_id')->constrained('users');
            $table->foreignId('secondary_nurse_id')->nullable()->constrained('users');
            $table->foreignId('assigned_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            
            // Assignment Status
            $table->enum('status', [
                'pending',
                'nurse_review',
                'accepted',
                'declined',
                'active',
                'on_hold',
                'completed',
                'cancelled',
                'reassigned'
            ])->default('pending');
            
            // Assignment Type
            $table->enum('assignment_type', [
                'single_nurse',
                'dual_nurse',
                'team_care',
                'rotating_care',
                'emergency_assignment'
            ])->default('single_nurse');
            
            // Timing and Duration
            $table->timestamp('assigned_at');
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            
            // Assignment Details
            $table->text('assignment_notes')->nullable();
            $table->text('special_requirements')->nullable();
            $table->json('nurse_qualifications_matched')->nullable(); // What qualifications matched
            
            // Response and Feedback
            $table->text('nurse_response_notes')->nullable();
            $table->text('decline_reason')->nullable();
            $table->integer('response_time_hours')->nullable(); // Time taken to respond
            
            // Location and Logistics
            $table->json('patient_address')->nullable();
            $table->decimal('estimated_travel_time', 5, 2)->nullable(); // in hours
            $table->decimal('distance_km', 8, 2)->nullable();
            
            // Workload Management
            $table->integer('estimated_hours_per_day')->nullable();
            $table->integer('total_estimated_hours')->nullable();
            $table->enum('intensity_level', ['light', 'moderate', 'intensive', 'critical'])->default('moderate');
            
            // Assignment Scoring (for matching algorithm)
            $table->integer('skill_match_score')->nullable(); // 0-100
            $table->integer('location_match_score')->nullable(); // 0-100
            $table->integer('availability_match_score')->nullable(); // 0-100
            $table->integer('workload_balance_score')->nullable(); // 0-100
            $table->integer('overall_match_score')->nullable(); // 0-100
            
            // Reassignment Tracking
            $table->foreignId('previous_assignment_id')->nullable()->constrained('care_assignments');
            $table->integer('reassignment_count')->default(0);
            $table->text('reassignment_reason')->nullable();
            
            // Performance Tracking
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->json('performance_metrics')->nullable();
            
            // Emergency and Priority
            $table->boolean('is_emergency')->default(false);
            $table->enum('priority_level', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->timestamp('emergency_assigned_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['care_plan_id', 'status']);
            $table->index(['primary_nurse_id', 'status', 'start_date']);
            $table->index(['patient_id', 'status']);
            $table->index(['assigned_at', 'status']);
            $table->index(['is_emergency', 'priority_level']);
            $table->index('overall_match_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_assignments');
    }
};