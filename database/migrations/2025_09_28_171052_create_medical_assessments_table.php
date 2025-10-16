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
        Schema::create('medical_assessments', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('nurse_id')->constrained('users')->onDelete('cascade');
            
            // Client Extended Information (beyond basic users table)
            $table->text('physical_address');
            $table->string('occupation')->nullable();
            $table->string('religion')->nullable();
            
            // Emergency Contacts
            $table->string('emergency_contact_1_name');
            $table->string('emergency_contact_1_relationship');
            $table->string('emergency_contact_1_phone', 20);
            $table->string('emergency_contact_2_name')->nullable();
            $table->string('emergency_contact_2_relationship')->nullable();
            $table->string('emergency_contact_2_phone', 20)->nullable();
            
            // Medical History
            $table->text('presenting_condition'); // Primary diagnosis/condition
            $table->text('past_medical_history')->nullable();
            $table->text('allergies')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('special_needs')->nullable(); // mobility, nutrition, devices
            
            // Initial Assessment - General Status
            $table->enum('general_condition', ['stable', 'unstable']);
            $table->enum('hydration_status', ['adequate', 'dehydrated']);
            $table->enum('nutrition_status', ['adequate', 'malnourished']);
            $table->enum('mobility_status', ['independent', 'assisted', 'bedridden']);
            
            // Wound/Ulcer Assessment
            $table->boolean('has_wounds')->default(false);
            $table->text('wound_description')->nullable();
            
            // Pain Assessment
            $table->integer('pain_level')->default(0)->comment('Pain scale 0-10');
            
            // Initial Vital Signs
            $table->json('initial_vitals'); // Will store: temperature, pulse, respiratory_rate, blood_pressure, spo2, weight
            
            // Nursing Assessment
            $table->text('initial_nursing_impression');
            
            // Assessment Status
            $table->enum('assessment_status', ['draft', 'completed', 'reviewed'])->default('completed');
            $table->timestamp('completed_at')->nullable();
            
            // Audit Fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['patient_id', 'created_at']);
            $table->index(['nurse_id', 'created_at']);
            $table->index('assessment_status');
            $table->index('general_condition');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_assessments');
    }
};