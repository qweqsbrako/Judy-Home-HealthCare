<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Services\GeocodeService;

class TimeTracking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nurse_id',
        'schedule_id',
        'patient_id',
        'care_plan_id',
        'start_time',
        'end_time',
        'paused_at',
        'total_duration_minutes',
        'total_pause_duration_minutes',
        'status',
        'session_type',
        'clock_in_location',
        'clock_out_location',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'work_notes',
        'pause_reason',
        'activities_performed',
        'break_count',
        'total_break_minutes',
        'requires_approval',
        'approved_by',
        'approved_at',
        'approval_notes',
        'clock_in_ip',
        'clock_out_ip',
        'device_info'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'paused_at' => 'datetime',
        'approved_at' => 'datetime',
        'activities_performed' => 'array',
        'requires_approval' => 'boolean',
        'clock_in_latitude' => 'decimal:8',
        'clock_in_longitude' => 'decimal:8',
        'clock_out_latitude' => 'decimal:8',
        'clock_out_longitude' => 'decimal:8',
    ];

    protected $appends = [
        'formatted_duration',
        'session_type_display',
        'clock_in_location_name',
        'clock_out_location_name'
    ];


    // Relationships
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->total_duration_minutes) {
            return '0h 0m';
        }

        $hours = floor($this->total_duration_minutes / 60);
        $minutes = $this->total_duration_minutes % 60;
        
        return "{$hours}h {$minutes}m";
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsPausedAttribute(): bool
    {
        return $this->status === 'paused';
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getCurrentDurationAttribute(): int
    {
        if (!$this->start_time) {
            return 0;
        }

        $endTime = $this->end_time ?? now();
        $duration = $this->start_time->diffInMinutes($endTime);
        
        // Subtract pause duration if any
        return max(0, $duration - $this->total_pause_duration_minutes);
    }

    public function getSessionTypeDisplayAttribute(): string
    {
        return match($this->session_type) {
            'scheduled_shift' => 'Scheduled Shift',
            'emergency_call' => 'Emergency Call',
            'overtime' => 'Overtime',
            'break_coverage' => 'Break Coverage',
            default => ucfirst(str_replace('_', ' ', $this->session_type))
        };
    }

    // Static methods for status/type options
    public static function getStatuses(): array
    {
        return [
            'active' => 'Active',
            'paused' => 'Paused',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
    }

    public static function getSessionTypes(): array
    {
        return [
            'scheduled_shift' => 'Scheduled Shift',
            'emergency_call' => 'Emergency Call',
            'overtime' => 'Overtime',
            'break_coverage' => 'Break Coverage'
        ];
    }

    // Instance methods
    public function clockIn(array $data = []): bool
    {
        if ($this->status !== 'active' || $this->start_time) {
            return false;
        }

        $this->update([
            'start_time' => now(),
            'clock_in_location' => $data['location'] ?? null,
            'clock_in_latitude' => $data['latitude'] ?? null,
            'clock_in_longitude' => $data['longitude'] ?? null,
            'clock_in_ip' => request()->ip(),
            'device_info' => $data['device_info'] ?? null,
        ]);

        return true;
    }

    public function clockOut(array $data = []): bool
    {
        if (!$this->start_time || $this->end_time) {
            return false;
        }

        // If currently paused, resume first
        if ($this->status === 'paused') {
            $this->resume();
        }

        $endTime = now();
        $totalDuration = $this->start_time->diffInMinutes($endTime) - $this->total_pause_duration_minutes;

        $this->update([
            'end_time' => $endTime,
            'total_duration_minutes' => max(0, $totalDuration),
            'status' => 'completed',
            'clock_out_location' => $data['location'] ?? null,
            'clock_out_latitude' => $data['latitude'] ?? null,
            'clock_out_longitude' => $data['longitude'] ?? null,
            'clock_out_ip' => request()->ip(),
            'work_notes' => $data['work_notes'] ?? $this->work_notes,
            'activities_performed' => $data['activities_performed'] ?? $this->activities_performed,
        ]);

        // Update the related schedule if exists
        if ($this->schedule) {
            $this->schedule->update([
                'actual_start_time' => $this->start_time,
                'actual_end_time' => $this->end_time,
                'actual_duration_minutes' => $this->total_duration_minutes,
                'status' => 'completed'
            ]);
        }

        return true;
    }

    public function pause(string $reason = null): bool
    {
        if ($this->status !== 'active' || !$this->start_time) {
            return false;
        }

        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
            'pause_reason' => $reason
        ]);

        return true;
    }

    public function resume(): bool
    {
        if ($this->status !== 'paused' || !$this->paused_at) {
            return false;
        }

        $pauseDuration = $this->paused_at->diffInMinutes(now());
        
        $this->update([
            'status' => 'active',
            'total_pause_duration_minutes' => $this->total_pause_duration_minutes + $pauseDuration,
            'paused_at' => null,
            'pause_reason' => null
        ]);

        return true;
    }

    public function cancel(string $reason = null): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'end_time' => now(),
            'work_notes' => $reason ? "Cancelled: {$reason}" : 'Session cancelled'
        ]);

        return true;
    }

    public function addBreak(int $minutes): bool
    {
        $this->update([
            'break_count' => $this->break_count + 1,
            'total_break_minutes' => $this->total_break_minutes + $minutes
        ]);

        return true;
    }

    public function approve(int $approvedById, string $notes = null): bool
    {
        if (!$this->requires_approval || $this->approved_at) {
            return false;
        }

        $this->update([
            'approved_by' => $approvedById,
            'approved_at' => now(),
            'approval_notes' => $notes
        ]);

        return true;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForNurse($query, int $nurseId)
    {
        return $query->where('nurse_id', $nurseId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true)->whereNull('approved_at');
    }

    // Static helper methods
    public static function getActiveSessionForNurse(int $nurseId): ?self
    {
        return self::where('nurse_id', $nurseId)
            ->whereIn('status', ['active', 'paused'])
            ->whereNull('end_time')
            ->first();
    }

    public static function getTotalHoursForNurse(int $nurseId, string $startDate, string $endDate): float
    {
        $totalMinutes = self::forNurse($nurseId)
            ->completed()
            ->forDateRange($startDate, $endDate)
            ->sum('total_duration_minutes');

        return round($totalMinutes / 60, 2);
    }

    public static function getWorkingSummaryForNurse(int $nurseId, string $date): array
    {
        $sessions = self::forNurse($nurseId)
            ->forDate($date)
            ->completed()
            ->get();

        return [
            'total_sessions' => $sessions->count(),
            'total_hours' => round($sessions->sum('total_duration_minutes') / 60, 2),
            'total_breaks' => $sessions->sum('break_count'),
            'total_break_minutes' => $sessions->sum('total_break_minutes'),
            'average_session_duration' => $sessions->count() > 0 
                ? round($sessions->avg('total_duration_minutes'), 2) 
                : 0
        ];
    }

      /**
     * Get human-readable clock in location
     *
     * @return string|null
     */
    public function getClockInLocationNameAttribute(): ?string
    {
        if ($this->clock_in_latitude && $this->clock_in_longitude) {
            $geocodeService = app(GeocodeService::class);
            return $geocodeService->getShortAddressFromCoordinates(
                $this->clock_in_latitude,
                $this->clock_in_longitude
            );
        }

        return $this->clock_in_location;
    }

    /**
     * Get human-readable clock out location
     *
     * @return string|null
     */
    public function getClockOutLocationNameAttribute(): ?string
    {
        if ($this->clock_out_latitude && $this->clock_out_longitude) {
            $geocodeService = app(GeocodeService::class);
            return $geocodeService->getShortAddressFromCoordinates(
                $this->clock_out_latitude,
                $this->clock_out_longitude
            );
        }

        return $this->clock_out_location;
    }
}