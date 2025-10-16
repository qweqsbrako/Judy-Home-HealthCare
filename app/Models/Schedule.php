<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Notifications\ScheduleReminder;
use Log;
use App\Services\SmsService; 
use Illuminate\Database\Eloquent\Relations\HasOne;


class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nurse_id',
        'care_plan_id', 
        'created_by',
        'schedule_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'shift_type',
        'status',
        'nurse_confirmed_at',
        'last_reminder_sent',
        'shift_notes',
        'location',
        'actual_start_time',
        'actual_end_time',
        'actual_duration_minutes',
        'reminder_count',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'nurse_confirmed_at' => 'datetime',
        'last_reminder_sent' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
    ];

    protected $appends = [
        'formatted_time_slot',
        'formatted_shift_type',
        'is_confirmed',
        'schedule_date_time',
    ];

    // Relationships
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class, 'care_plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ... rest of the existing methods remain the same ...

    // Scopes
    public function scopeToday($query)
    {
        return $query->where('schedule_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('schedule_date', '>=', today());
    }

    public function scopeForNurse($query, $nurseId)
    {
        return $query->where('nurse_id', $nurseId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getIsConfirmedAttribute(): bool
    {
        return !is_null($this->nurse_confirmed_at);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getFormattedShiftTypeAttribute(): string
    {
        $types = static::getShiftTypes();
        return $types[$this->shift_type] ?? ucwords(str_replace('_', ' ', $this->shift_type));
    }

    public function getFormattedTimeSlotAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' . Carbon::parse($this->end_time)->format('H:i');
    }

  public function getScheduleDateTimeAttribute(): ?Carbon
    {
        if (!$this->schedule_date || !$this->start_time) {
            return null;
        }
        
        return $this->schedule_date->setTimeFromTimeString($this->start_time);
    }

    public function getActualDurationAttribute(): ?int
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            return $this->actual_start_time->diffInMinutes($this->actual_end_time);
        }
        return null;
    }

    // Methods
    public function confirmByNurse(): bool
    {
        return $this->update(['nurse_confirmed_at' => now()]);
    }

    public function startShift(): bool
    {
        return $this->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);
    }

    public function endShift(): bool
    {
        return $this->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'actual_duration_minutes' => $this->getActualDurationAttribute(),
        ]);
    }

    public function cancel(string $reason = null): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'shift_notes' => $reason ? $this->shift_notes . "\nCancellation reason: " . $reason : $this->shift_notes,
        ]);
    }


    public function hasTimeConflictWith(Schedule $otherSchedule): bool
    {
        if ($this->schedule_date->ne($otherSchedule->schedule_date)) {
            return false;
        }

        $thisStart = Carbon::parse($this->start_time);
        $thisEnd = Carbon::parse($this->end_time);
        $otherStart = Carbon::parse($otherSchedule->start_time);
        $otherEnd = Carbon::parse($otherSchedule->end_time);

        return $thisStart->lt($otherEnd) && $thisEnd->gt($otherStart);
    }

    // Static methods for consistency with controller expectations
    public static function getShiftTypes(): array
    {
        return [
            'morning_shift' => 'Morning Shift',
            'afternoon_shift' => 'Afternoon Shift',
            'evening_shift' => 'Evening Shift',
            'night_shift' => 'Night Shift',
            'custom_shift' => 'Custom Shift',
        ];
    }

    // Alias for controller compatibility
    public static function getScheduleTypes(): array
    {
        return static::getShiftTypes();
    }

    public static function getStatuses(): array
    {
        return [
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function getFrequencyTypes(): array
    {
        return [
            'one_time' => 'One Time',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-Weekly',
            'monthly' => 'Monthly',
        ];
    }

    // Calculate duration when times are set
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($schedule) {
            if ($schedule->start_time && $schedule->end_time) {
                $start = Carbon::parse($schedule->start_time);
                $end = Carbon::parse($schedule->end_time);
                $schedule->duration_minutes = $start->diffInMinutes($end);
            }
        });
    }


        /**
     * Send reminder notification to nurse via email and SMS
     *
     * @return array Results of the reminder sending
     */
    public function sendReminder(): array
    {
        $results = [
            'email' => ['sent' => false, 'message' => ''],
            'sms' => ['sent' => false, 'message' => ''],
            'overall_success' => false
        ];

        // GUARD: Check if reminder was already sent TODAY
        if ($this->last_reminder_sent && 
            $this->last_reminder_sent->isToday()) {
            
            Log::info('Reminder skipped - already sent today', [
                'schedule_id' => $this->id,
                'last_sent' => $this->last_reminder_sent->toDateTimeString()
            ]);
            
            $results['email']['message'] = 'Reminder already sent today';
            $results['sms']['message'] = 'Reminder already sent today';
            return $results;
        }

        // GUARD: Check max reminders
        if ($this->reminder_count >= 3) {
            Log::info('Reminder skipped - max count reached', [
                'schedule_id' => $this->id,
                'reminder_count' => $this->reminder_count
            ]);
            
            $results['email']['message'] = 'Maximum reminders reached';
            $results['sms']['message'] = 'Maximum reminders reached';
            return $results;
        }

        // Update timestamp IMMEDIATELY before sending (prevents race conditions)
        $this->update([
            'last_reminder_sent' => now()
        ]);

        try {
            // Get the nurse
            if (!$this->nurse) {
                Log::warning('Cannot send reminder - nurse not found', [
                    'schedule_id' => $this->id
                ]);
                
                $results['email']['message'] = 'Nurse not found';
                $results['sms']['message'] = 'Nurse not found';
                return $results;
            }

            // 1. Send Email Notification
            try {
                $this->nurse->notify(new ScheduleReminder($this));
                $results['email']['sent'] = true;
                $results['email']['message'] = 'Email sent successfully';
                
                Log::info('Schedule reminder email sent', [
                    'schedule_id' => $this->id,
                    'nurse_id' => $this->nurse_id,
                    'nurse_email' => $this->nurse->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send schedule reminder email', [
                    'schedule_id' => $this->id,
                    'nurse_id' => $this->nurse_id,
                    'error' => $e->getMessage()
                ]);
                $results['email']['message'] = 'Email failed: ' . $e->getMessage();
            }

            // 2. Send SMS Notification
            try {
                $smsService = new \App\Services\SmsService();
                
                $smsData = [
                    'nurse_name' => $this->nurse->first_name . ' ' . $this->nurse->last_name,
                    'date' => $this->schedule_date->format('l, M d, Y'),
                    'time' => $this->formatted_time_slot,
                    'patient_name' => $this->carePlan?->patient 
                        ? $this->carePlan->patient->first_name . ' ' . $this->carePlan->patient->last_name 
                        : null,
                    'location' => $this->location
                ];

                $smsResult = $smsService->sendScheduleReminder($this->nurse->phone, $smsData);
                
                if ($smsResult['success']) {
                    $results['sms']['sent'] = true;
                    $results['sms']['message'] = 'SMS sent successfully';
                    
                    Log::info('Schedule reminder SMS sent', [
                        'schedule_id' => $this->id,
                        'nurse_id' => $this->nurse_id,
                        'nurse_phone' => $this->nurse->phone
                    ]);
                } else {
                    $results['sms']['message'] = 'SMS failed: ' . $smsResult['message'];
                    
                    Log::warning('Failed to send schedule reminder SMS', [
                        'schedule_id' => $this->id,
                        'nurse_id' => $this->nurse_id,
                        'error' => $smsResult['message']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('SMS Service Exception for schedule reminder', [
                    'schedule_id' => $this->id,
                    'nurse_id' => $this->nurse_id,
                    'error' => $e->getMessage()
                ]);
                $results['sms']['message'] = 'SMS service error: ' . $e->getMessage();
            }

            // Only increment counter AFTER successful send
            $results['overall_success'] = $results['email']['sent'] || $results['sms']['sent'];
            
            if ($results['overall_success']) {
                $this->increment('reminder_count');
                
                Log::info('Schedule reminder sent successfully', [
                    'schedule_id' => $this->id,
                    'email_sent' => $results['email']['sent'],
                    'sms_sent' => $results['sms']['sent'],
                    'reminder_count' => $this->reminder_count
                ]);
            } else {
                // Rollback timestamp if both failed
                $this->update(['last_reminder_sent' => null]);
            }

            return $results;

        } catch (\Exception $e) {
            // Rollback timestamp on unexpected error
            $this->update(['last_reminder_sent' => null]);
            
            Log::error('Unexpected error in sendReminder', [
                'schedule_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $results['email']['message'] = 'Unexpected error';
            $results['sms']['message'] = 'Unexpected error';
            return $results;
        }
    }

    /**
     * Send bulk reminders for multiple schedules
     *
     * @param \Illuminate\Support\Collection $schedules
     * @return array Summary of results
     */
    public static function sendBulkReminders($schedules): array
    {
        $summary = [
            'total' => $schedules->count(),
            'success' => 0,
            'failed' => 0,
            'email_sent' => 0,
            'sms_sent' => 0,
            'details' => []
        ];

        foreach ($schedules as $schedule) {
            $result = $schedule->sendReminder();
            
            if ($result['overall_success']) {
                $summary['success']++;
            } else {
                $summary['failed']++;
            }

            if ($result['email']['sent']) {
                $summary['email_sent']++;
            }

            if ($result['sms']['sent']) {
                $summary['sms_sent']++;
            }

            $summary['details'][] = [
                'schedule_id' => $schedule->id,
                'nurse_name' => $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name,
                'result' => $result
            ];
        }

        Log::info('Bulk schedule reminders sent', $summary);

        return $summary;
    }

    public function timeTracking(): HasOne
    {
        return $this->hasOne(TimeTracking::class, 'schedule_id');
    }
}