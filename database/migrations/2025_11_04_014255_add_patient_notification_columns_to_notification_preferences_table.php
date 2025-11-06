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
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Add patient-specific notification preferences
            $table->boolean('appointment_reminders')->default(true)->after('medication_reminders');
            $table->boolean('vitals_tracking')->default(true)->after('patient_vitals_alert');
            $table->boolean('health_tips')->default(true)->after('vitals_tracking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'appointment_reminders',
                'vitals_tracking',
                'health_tips',
            ]);
        });
    }
};