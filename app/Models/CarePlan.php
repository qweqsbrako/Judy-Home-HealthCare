<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarePlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'created_by',
        'approved_by',
        'primary_nurse_id',
        'secondary_nurse_id',
        'title',
        'description',
        'care_type',
        'status',
        'start_date',
        'end_date',
        'frequency',
        'custom_frequency_details',
        'care_tasks',
        'priority',
        'approved_at',
        'completion_percentage',
        'assignment_notes',
        'completed_tasks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'care_tasks' => 'array',
        'approved_at' => 'datetime',
        'completion_percentage' => 'integer',
        'completed_tasks' => 'array',
    ];

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function primaryNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_nurse_id');
    }

    public function secondaryNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secondary_nurse_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByCareType($query, $careType)
    {
        return $query->where('care_type', $careType);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedToNurse($query, $nurseId)
    {
        return $query->where(function($q) use ($nurseId) {
            $q->where('primary_nurse_id', $nurseId)
              ->orWhere('secondary_nurse_id', $nurseId);
        });
    }

    // Helper methods
    public function isNurseAssigned($nurseId): bool
    {
        return $this->primary_nurse_id == $nurseId || $this->secondary_nurse_id == $nurseId;
    }

    public function getAssignedNurses()
    {
        $nurses = collect();
        
        if ($this->primaryNurse) {
            $nurses->push($this->primaryNurse);
        }
        
        if ($this->secondaryNurse && $this->secondary_nurse_id !== $this->primary_nurse_id) {
            $nurses->push($this->secondaryNurse);
        }
        
        return $nurses;
    }

    // Accessors
    public function getFormattedCareTypeAttribute(): string
    {
        return str_replace('_', ' ', ucwords($this->care_type));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsApprovedAttribute(): bool
    {
        return in_array($this->status, ['approved', 'active', 'on_hold', 'completed']);
    }

    public function getHasNurseAssignedAttribute(): bool
    {
        return !is_null($this->primary_nurse_id);
    }

    public function getDurationInDaysAttribute(): int
    {
        if (!$this->end_date) {
            return 0;
        }
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getPlanNameAttribute(): string
    {
        return $this->title ?: 'Untitled Care Plan';
    }

    public function getFormattedPriorityAttribute(): string
    {
        return ucfirst($this->priority);
    }

    // Methods
    public function approve(User $user): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function activate(): bool
    {
        if (!in_array($this->status, ['approved', 'on_hold'])) {
            return false;
        }

        return $this->update(['status' => 'active']);
    }

    public function complete(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completion_percentage' => 100,
        ]);
    }

    public function hold(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->update(['status' => 'on_hold']);
    }

    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    public function updateProgress(int $percentage): bool
    {
        return $this->update([
            'completion_percentage' => min(100, max(0, $percentage)),
        ]);
    }

    public function assignNurse(User $primaryNurse, User $secondaryNurse = null, string $notes = null): bool
    {
        if ($primaryNurse->role !== 'nurse') {
            return false;
        }

        if ($secondaryNurse && $secondaryNurse->role !== 'nurse') {
            return false;
        }

        return $this->update([
            'primary_nurse_id' => $primaryNurse->id,
            'secondary_nurse_id' => $secondaryNurse?->id,
            'assignment_notes' => $notes,
        ]);
    }

    // Static methods for enum values
    public static function getCareTypes(): array
    {
        return [
            'general_care' => 'General Care',
            'elderly_care' => 'Elderly Care',
            'pediatric_care' => 'Pediatric Care',
            'chronic_disease_management' => 'Chronic Disease Management',
            'palliative_care' => 'Palliative Care',
            'rehabilitation_care' => 'Rehabilitation Care',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'active' => 'Active',
            'completed' => 'Completed',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            'low' => 'Low Priority',
            'medium' => 'Medium Priority',
            'high' => 'High Priority',
            'critical' => 'Critical Priority',
        ];
    }

    public static function getFrequencies(): array
    {
        return [
            'once_daily' => 'Once Daily',
            'twice_daily' => 'Twice Daily',
            'three_times_daily' => 'Three Times Daily',
            'every_12_hours' => 'Every 12 Hours',
            'every_8_hours' => 'Every 8 Hours',
            'every_6_hours' => 'Every 6 Hours',
            'every_4_hours' => 'Every 4 Hours',
            'weekly' => 'Weekly',
            'twice_weekly' => 'Twice Weekly',
            'as_needed' => 'As Needed',
            'custom' => 'Custom',
        ];
    }

    public static function getAssignmentTypes(): array
    {
        return [
            'single_nurse' => 'Single Nurse',
            'team_care' => 'Team Care',
        ];
    }
}