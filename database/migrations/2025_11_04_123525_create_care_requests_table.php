<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_nurse_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('medical_assessment_id')->nullable()->constrained('medical_assessments')->onDelete('set null');
            
            // Request details
            $table->enum('care_type', [
                'general_nursing',
                'elderly_care',
                'post_surgical',
                'chronic_disease',
                'palliative_care',
                'rehabilitation',
                'wound_care',
                'medication_management'
            ]);
            $table->enum('urgency_level', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->text('description');
            $table->text('special_requirements')->nullable();
            $table->string('preferred_language')->nullable();
            $table->date('preferred_start_date')->nullable();
            $table->string('preferred_time')->nullable(); // morning, afternoon, evening, night
            
            // Location
            $table->text('service_address');
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status tracking
            $table->enum('status', [
                'pending_payment',
                'payment_received',
                'nurse_assigned',
                'assessment_scheduled',
                'assessment_completed',
                'under_review',
                'care_plan_created',
                'awaiting_care_payment',
                'care_payment_received',
                'care_active',
                'care_completed',
                'cancelled',
                'rejected'
            ])->default('pending_payment');
            
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Timestamps
            $table->timestamp('assessment_scheduled_at')->nullable();
            $table->timestamp('assessment_completed_at')->nullable();
            $table->timestamp('care_started_at')->nullable();
            $table->timestamp('care_ended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['patient_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_requests');
    }
};