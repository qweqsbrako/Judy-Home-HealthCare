<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // Required for mass assignment
        'all_notifications',
        
        // Care & Patient notifications
        'new_patient_assignment',
        'careplan_updates',
        'patient_vitals_alert',
        'medication_reminders',
        
        // Schedule notifications
        'shift_reminders',
        'shift_changes',
        'clock_in_reminders',
        
        // Communication notifications
        'transport_requests',
        'incident_reports',
        
        // System notifications
        'system_updates',
        'security_alerts',
        
        // Channel preferences
        'email_notifications',
        'sms_notifications',
        'health_tips',
        
        // Quiet hours
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'appointment_reminders',
        'vitals_tracking',          

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'all_notifications' => 'boolean',
        'new_patient_assignment' => 'boolean',
        'careplan_updates' => 'boolean',
        'patient_vitals_alert' => 'boolean',
        'medication_reminders' => 'boolean',
        'shift_reminders' => 'boolean',
        'shift_changes' => 'boolean',
        'clock_in_reminders' => 'boolean',
        'transport_requests' => 'boolean',
        'incident_reports' => 'boolean',
        'system_updates' => 'boolean',
        'security_alerts' => 'boolean',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
        'appointment_reminders' => 'boolean',
        'vitals_tracking' => 'boolean', 
        'health_tips' => 'boolean'
    ];

    /**
     * Get the user that owns the notification preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}