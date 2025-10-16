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
        Schema::create('care_plans', function (Blueprint $table) {
            $table->id();

            // Patient and Doctor Information
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');

            // Care Plan Details
            $table->string('title');
            $table->text('description');
            $table->enum('care_type', [
                'general_care',
                'elderly_care',
                'pediatric_care',
                'chronic_disease_management',
                'palliative_care',
                'rehabilitation_care'
            ]);

            // Care Plan Status
            $table->enum('status', [
                'draft',
                'pending_approval',
                // 'approved',
                'active',
                // 'on_hold',
                'completed',
                // 'cancelled'
            ])->default('draft');

            // Care Duration and Frequency
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('frequency', [
                'once_daily',
                'twice_daily',
                'three_times_daily',
                'every_12_hours',
                'every_8_hours',
                'every_6_hours',
                'every_4_hours',
                'weekly',
                'twice_weekly',
                'as_needed',
                'custom'
            ]);
            $table->text('custom_frequency_details')->nullable();

            // Care Tasks (JSON)
            $table->json('care_tasks'); // Array of tasks with details

            // Priority and Complexity
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

            // Approval and Reviews
            $table->timestamp('approved_at')->nullable();

            // Progress Tracking
            $table->integer('completion_percentage')->default(0);

            $table->foreignId('primary_nurse_id')->constrained('users');
            $table->foreignId('secondary_nurse_id')->nullable()->constrained('users');
            $table->text('assignment_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'created_at']);
            $table->index(['care_type', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_plans');
    }
};
