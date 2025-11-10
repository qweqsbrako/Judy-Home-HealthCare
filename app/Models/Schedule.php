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

    public function timeTracking(): HasOne
    {
        return $this->hasOne(TimeTracking::class, 'schedule_id');
    }

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
        // Parse times correctly
        $start = $this->parseTime($this->start_time);
        $end = $this->parseTime($this->end_time);
        
        // Handle null values
        if (!$start || !$end) {
            return 'Not scheduled';
        }
        
        return $start->format('H:i') . ' - ' . $end->format('H:i');
    }

    public function getScheduleDateTimeAttribute(): ?Carbon
    {
        if (!$this->schedule_date || !$this->start_time) {
            return null;
        }
        
        // ✅ FIXED: Properly combine date and time
        $scheduleDate = Carbon::parse($this->schedule_date);
        $startTime = $this->parseTime($this->start_time);
        
        return Carbon::create(
            $scheduleDate->year,
            $scheduleDate->month,
            $scheduleDate->day,
            $startTime->hour,
            $startTime->minute,
            $startTime->second
        );
    }

    public function getActualDurationAttribute(): ?int
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            return abs($this->actual_end_time->diffInMinutes($this->actual_start_time));
        }
        return null;
    }

    //Helper method to parse time correctly
    private function parseTime($time): ?Carbon
    {
        // Handle null values
        if ($time === null) {
            return null;
        }
        
        // If it's already a Carbon instance, return it
        if ($time instanceof Carbon) {
            return $time;
        }
        
        // If it's a string with full datetime
        if (is_string($time) && strlen($time) > 8) {
            $parsed = Carbon::parse($time);
            return Carbon::createFromTime($parsed->hour, $parsed->minute, $parsed->second);
        }
        
        // If it's just a time string (HH:MM:SS or HH:MM)
        if (is_string($time)) {
            return Carbon::createFromTimeString($time);
        }
        
        // Fallback to null for any other cases
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

        $thisStart = $this->parseTime($this->start_time);
        $thisEnd = $this->parseTime($this->end_time);
        $otherStart = $this->parseTime($otherSchedule->start_time);
        $otherEnd = $this->parseTime($otherSchedule->end_time);

        return $thisStart->lt($otherEnd) && $thisEnd->gt($otherStart);
    }

    // Static methods
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

    // ✅ FIXED: Boot method with better time handling
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($schedule) {
            if ($schedule->start_time && $schedule->end_time && $schedule->schedule_date) {
                try {
                    // Get the schedule date
                    $scheduleDate = Carbon::parse($schedule->schedule_date);
                    
                    // Parse times correctly
                    $startTime = self::parseTimeStatic($schedule->start_time);
                    $endTime = self::parseTimeStatic($schedule->end_time);
                    
                    // Create full datetime instances
                    $start = Carbon::create(
                        $scheduleDate->year,
                        $scheduleDate->month,
                        $scheduleDate->day,
                        $startTime->hour,
                        $startTime->minute,
                        $startTime->second
                    );
                    
                    $end = Carbon::create(
                        $scheduleDate->year,
                        $scheduleDate->month,
                        $scheduleDate->day,
                        $endTime->hour,
                        $endTime->minute,
                        $endTime->second
                    );
                    
                    // If end is before start, it means the shift goes to next day
                    if ($end->lt($start)) {
                        $end->addDay();
                    }
                    
                    // Calculate duration in minutes (always positive)
                    $duration = abs($end->diffInMinutes($start));
                    
                    // Only update if we got a valid duration
                    if ($duration > 0) {
                        $schedule->duration_minutes = $duration;
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('Error calculating schedule duration', [
                        'schedule_id' => $schedule->id ?? 'new',
                        'error' => $e->getMessage(),
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'schedule_date' => $schedule->schedule_date
                    ]);
                }
            }
        });
    }

    // ✅ NEW: Static version of parseTime for boot method
    private static function parseTimeStatic($time): Carbon
    {
        if ($time instanceof Carbon) {
            return $time;
        }
        
        if (strlen($time) > 8) {
            $parsed = Carbon::parse($time);
            return Carbon::createFromTime($parsed->hour, $parsed->minute, $parsed->second);
        }
        
        return Carbon::createFromTimeString($time);
    }

    /**
     * Send reminder notification to nurse via email and SMS
     */
    public function sendReminder(): array
    {
        $results = [
            'email' => ['sent' => false, 'message' => ''],
            'sms' => ['sent' => false, 'message' => ''],
            'overall_success' => false
        ];

        // Check if reminder was already sent today
        if ($this->last_reminder_sent && $this->last_reminder_sent->isToday()) {
            Log::info('Reminder skipped - already sent today', [
                'schedule_id' => $this->id,
                'last_sent' => $this->last_reminder_sent->toDateTimeString()
            ]);
            
            $results['email']['message'] = 'Reminder already sent today';
            $results['sms']['message'] = 'Reminder already sent today';
            return $results;
        }

        // Check max reminders
        if ($this->reminder_count >= 3) {
            Log::info('Reminder skipped - max count reached', [
                'schedule_id' => $this->id,
                'reminder_count' => $this->reminder_count
            ]);
            
            $results['email']['message'] = 'Maximum reminders reached';
            $results['sms']['message'] = 'Maximum reminders reached';
            return $results;
        }

        // Update timestamp immediately
        $this->update(['last_reminder_sent' => now()]);

        try {
            if (!$this->nurse) {
                Log::warning('Cannot send reminder - nurse not found', [
                    'schedule_id' => $this->id
                ]);
                
                $results['email']['message'] = 'Nurse not found';
                $results['sms']['message'] = 'Nurse not found';
                return $results;
            }

            // Send email
            try {
                $this->nurse->notify(new ScheduleReminder($this));
                $results['email']['sent'] = true;
                $results['email']['message'] = 'Email sent successfully';
                
                Log::info('Schedule reminder email sent', [
                    'schedule_id' => $this->id,
                    'nurse_id' => $this->nurse_id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send schedule reminder email', [
                    'schedule_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                $results['email']['message'] = 'Email failed: ' . $e->getMessage();
            }

            // Send SMS
            try {
                $smsService = new SmsService();
                
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
                        'nurse_phone' => $this->nurse->phone
                    ]);
                } else {
                    $results['sms']['message'] = 'SMS failed: ' . $smsResult['message'];
                }
            } catch (\Exception $e) {
                Log::error('SMS Service Exception', [
                    'schedule_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                $results['sms']['message'] = 'SMS service error: ' . $e->getMessage();
            }

            $results['overall_success'] = $results['email']['sent'] || $results['sms']['sent'];
            
            if ($results['overall_success']) {
                $this->increment('reminder_count');
            } else {
                $this->update(['last_reminder_sent' => null]);
            }

            return $results;

        } catch (\Exception $e) {
            $this->update(['last_reminder_sent' => null]);
            
            Log::error('Unexpected error in sendReminder', [
                'schedule_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            $results['email']['message'] = 'Unexpected error';
            $results['sms']['message'] = 'Unexpected error';
            return $results;
        }
    }

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
    
    /**
     * Get feedback for this schedule
     */
    public function feedback()
    {
        return $this->hasOne(PatientFeedback::class, 'schedule_id');
    }
}