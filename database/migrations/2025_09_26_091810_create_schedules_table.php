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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            
            // Core Relationships
            $table->foreignId('nurse_id')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            
            // Schedule Timing
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes'); // Calculated field
            $table->integer('reminder_count')->default(0);
            $table->foreignId('care_plan_id')->nullable()->constrained('care_plans')->onDelete('set null');
            

            // Schedule Type
            $table->enum('shift_type', [
                'morning_shift',
                'afternoon_shift',
                'evening_shift',
                'night_shift',
                'custom_shift'
            ])->default('morning_shift');
            
            // Status
            $table->enum('status', [
                'scheduled',
                'confirmed',
                'cancelled',
                'completed'
            ])->default('scheduled');
            
            // Confirmation
            $table->timestamp('nurse_confirmed_at')->nullable();
            $table->timestamp('last_reminder_sent')->nullable();

            
            // Basic Information
            $table->text('shift_notes')->nullable();
            $table->text('location')->nullable(); // Where the nurse should report
            
            // Tracking
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('care_plan_id');
            $table->index(['schedule_date', 'nurse_id']);
            $table->index(['nurse_id', 'status']);
            $table->index(['schedule_date', 'status']);
            $table->unique(['nurse_id', 'schedule_date', 'start_time']); // Prevent double booking
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};