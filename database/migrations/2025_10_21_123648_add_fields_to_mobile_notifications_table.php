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
        // Create new comprehensive notification preferences table
        // NOTE: We are NOT dropping mobile_notifications table
        // mobile_notifications is still used for Password & Security screen
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Master toggle
            $table->boolean('all_notifications')->default(true);
            
            // Care & Patient notifications
            $table->boolean('new_patient_assignment')->default(true);
            $table->boolean('careplan_updates')->default(true);
            $table->boolean('patient_vitals_alert')->default(true);
            $table->boolean('medication_reminders')->default(true);
            
            // Schedule notifications
            $table->boolean('shift_reminders')->default(true);
            $table->boolean('shift_changes')->default(true);
            $table->boolean('clock_in_reminders')->default(true);
            
            // Communication notifications
            $table->boolean('transport_requests')->default(true);
            $table->boolean('incident_reports')->default(false);
            
            // System notifications
            $table->boolean('system_updates')->default(false);
            $table->boolean('security_alerts')->default(true);
            
            // Channel preferences
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            
            // Quiet hours
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->nullable()->default('22:00:00');
            $table->time('quiet_hours_end')->nullable()->default('07:00:00');
            
            $table->timestamps();

            // Ensure one record per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};