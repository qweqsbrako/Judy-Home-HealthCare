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
        Schema::create('time_trackings', function (Blueprint $table) {
            $table->id();
            
            // Core Relationships
            $table->foreignId('nurse_id')->constrained('users');
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->onDelete('set null');
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('care_plan_id')->nullable()->constrained('care_plans')->onDelete('set null');
            
            // Time Tracking
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->integer('total_duration_minutes')->default(0);
            $table->integer('total_pause_duration_minutes')->default(0);
            
            // Status and Type
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->enum('session_type', ['scheduled_shift', 'emergency_call', 'overtime', 'break_coverage'])->default('scheduled_shift');
            
            // Location Tracking
            $table->string('clock_in_location')->nullable(); // Address where clocked in
            $table->string('clock_out_location')->nullable(); // Address where clocked out
            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();
            
            // Additional Information
            $table->text('work_notes')->nullable(); // What was accomplished during the shift
            $table->text('pause_reason')->nullable(); // Reason for pausing if applicable
            $table->json('activities_performed')->nullable(); // List of care activities
            
            // Break Tracking
            $table->integer('break_count')->default(0);
            $table->integer('total_break_minutes')->default(0);
            
            // Verification and Approval
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // System Tracking
            $table->ipAddress('clock_in_ip')->nullable();
            $table->ipAddress('clock_out_ip')->nullable();
            $table->string('device_info')->nullable(); // Mobile device info
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['nurse_id', 'start_time']);
            $table->index(['status', 'nurse_id']);
            $table->index(['schedule_id', 'status']);
            $table->index(['patient_id', 'start_time']);
            $table->index(['created_at', 'nurse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_trackings');
    }
};