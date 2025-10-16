<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'care_plan_id',
        'patient_id',
        'primary_nurse_id',
        'secondary_nurse_id',
        'assigned_by',
        'approved_by',
        'status',
        'assignment_type',
        'assigned_at',
        'start_date',
        'end_date',
        'accepted_at',
        'declined_at',
        'assignment_notes',
        'special_requirements',
        'nurse_qualifications_matched',
        'nurse_response_notes',
        'decline_reason',
        'response_time_hours',
        'patient_address',
        'estimated_travel_time',
        'distance_km',
        'estimated_hours_per_day',
        'total_estimated_hours',
        'intensity_level',
        'skill_match_score',
        'location_match_score',
        'availability_match_score',
        'workload_balance_score',
        'overall_match_score',
        'previous_assignment_id',
        'reassignment_count',
        'reassignment_reason',
        'actual_start_date',
        'actual_end_date',
        'completion_percentage',
        'performance_metrics',
        'is_emergency',
        'priority_level',
        'emergency_assigned_at',
        'admin_override',
        'admin_override_reason',
        'admin_override_at',
        'admin_override_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'emergency_assigned_at' => 'datetime',
        'nurse_qualifications_matched' => 'array',
        'patient_address' => 'array',
        'performance_metrics' => 'array',
        'is_emergency' => 'boolean',
        'estimated_travel_time' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'admin_override' => 'boolean',
        'admin_override_at' => 'datetime',
    ];

    // Relationships
    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function primaryNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_nurse_id');
    }

    public function secondaryNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secondary_nurse_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function previousAssignment(): BelongsTo
    {
        return $this->belongsTo(CareAssignment::class, 'previous_assignment_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function reassignments(): HasMany
    {
        return $this->hasMany(CareAssignment::class, 'previous_assignment_id');
    }

    public function adminOverrideBy()
    {
        return $this->belongsTo(User::class, 'admin_override_by');
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'nurse_review']);
    }

    public function scopeForNurse($query, $nurseId)
    {
        return $query->where(function($q) use ($nurseId) {
            $q->where('primary_nurse_id', $nurseId)
              ->orWhere('secondary_nurse_id', $nurseId);
        });
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeEmergency($query)
    {
        return $query->where('is_emergency', true);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority_level', $priority);
    }

    // Accessors & Mutators
    public function getIsAcceptedAttribute(): bool
    {
        return $this->status === 'accepted';
    }

    public function getIsDeclinedAttribute(): bool
    {
        return $this->status === 'declined';
    }

    public function getIsPendingResponseAttribute(): bool
    {
        return in_array($this->status, ['pending', 'nurse_review']);
    }

    public function getFormattedAssignmentTypeAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->assignment_type));
    }

    public function getFormattedIntensityLevelAttribute(): string
    {
        return ucfirst($this->intensity_level);
    }

    public function getResponseTimeInHoursAttribute(): ?int
    {
        if ($this->accepted_at) {
            return $this->assigned_at->diffInHours($this->accepted_at);
        }
        
        if ($this->declined_at) {
            return $this->assigned_at->diffInHours($this->declined_at);
        }

        return null;
    }

    // Methods
    public function accept(User $nurse, string $notes = null): bool
    {
        if ($this->primary_nurse_id !== $nurse->id && $this->secondary_nurse_id !== $nurse->id) {
            return false;
        }

        $responseTime = $this->assigned_at->diffInHours(now());

        return $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'nurse_response_notes' => $notes,
            'response_time_hours' => $responseTime,
        ]);
    }

    public function decline(User $nurse, string $reason): bool
    {
        if ($this->primary_nurse_id !== $nurse->id && $this->secondary_nurse_id !== $nurse->id) {
            return false;
        }

        $responseTime = $this->assigned_at->diffInHours(now());

        return $this->update([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $reason,
            'response_time_hours' => $responseTime,
        ]);
    }

    public function activate(): bool
    {
        if ($this->status !== 'accepted') {
            return false;
        }

        return $this->update([
            'status' => 'active',
            'actual_start_date' => now(),
        ]);
    }

    public function complete(): bool
    {
        return $this->update([
            'status' => 'completed',
            'actual_end_date' => now(),
            'completion_percentage' => 100,
        ]);
    }

    public function reassign(User $newNurse, User $assignedBy, string $reason): CareAssignment
    {
        // Create new assignment
        $newAssignment = $this->replicate();
        $newAssignment->primary_nurse_id = $newNurse->id;
        $newAssignment->assigned_by = $assignedBy->id;
        $newAssignment->status = 'pending';
        $newAssignment->assigned_at = now();
        $newAssignment->previous_assignment_id = $this->id;
        $newAssignment->reassignment_count = $this->reassignment_count + 1;
        $newAssignment->reassignment_reason = $reason;
        $newAssignment->save();

        // Update current assignment
        $this->update([
            'status' => 'reassigned',
            'reassignment_reason' => $reason,
        ]);

        return $newAssignment;
    }

    public function calculateMatchScore(User $nurse): int
    {
        $skillScore = $this->calculateSkillMatchScore($nurse);
        $locationScore = $this->calculateLocationMatchScore($nurse);
        $availabilityScore = $this->calculateAvailabilityMatchScore($nurse);
        $workloadScore = $this->calculateWorkloadBalanceScore($nurse);

        $overallScore = ($skillScore + $locationScore + $availabilityScore + $workloadScore) / 4;

        $this->update([
            'skill_match_score' => $skillScore,
            'location_match_score' => $locationScore,
            'availability_match_score' => $availabilityScore,
            'workload_balance_score' => $workloadScore,
            'overall_match_score' => $overallScore,
        ]);

        return $overallScore;
    }

    private function calculateSkillMatchScore(User $nurse): int
    {
        if (!$this->carePlan->matchesNurseQualifications($nurse)) {
            return 0;
        }

        $score = 70; // Base score for meeting requirements

        // Bonus for additional experience
        $experienceBonus = min(20, ($nurse->years_experience - $this->carePlan->min_years_experience) * 2);
        $score += $experienceBonus;

        // Bonus for specialization match
        if ($nurse->specialization === $this->carePlan->care_type) {
            $score += 10;
        }

        return min(100, $score);
    }

    private function calculateLocationMatchScore(User $nurse): int
    {
        // This would integrate with a mapping service
        // For now, return a placeholder based on estimated travel time
        if ($this->estimated_travel_time <= 0.5) return 100;
        if ($this->estimated_travel_time <= 1) return 80;
        if ($this->estimated_travel_time <= 2) return 60;
        if ($this->estimated_travel_time <= 3) return 40;
        return 20;
    }

    private function calculateAvailabilityMatchScore(User $nurse): int
    {
        // Check nurse's existing schedules during the assignment period
        $conflictingSchedules = Schedule::where('nurse_id', $nurse->id)
            ->whereBetween('schedule_date', [$this->start_date->toDateString(), $this->end_date?->toDateString()])
            ->count();

        if ($conflictingSchedules === 0) return 100;
        if ($conflictingSchedules <= 2) return 80;
        if ($conflictingSchedules <= 5) return 60;
        if ($conflictingSchedules <= 10) return 40;
        return 20;
    }

    private function calculateWorkloadBalanceScore(User $nurse): int
    {
        // Calculate nurse's current workload
        $currentActiveAssignments = CareAssignment::where('primary_nurse_id', $nurse->id)
            ->where('status', 'active')
            ->count();

        if ($currentActiveAssignments <= 3) return 100;
        if ($currentActiveAssignments <= 5) return 80;
        if ($currentActiveAssignments <= 7) return 60;
        if ($currentActiveAssignments <= 10) return 40;
        return 20;
    }

    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending Assignment',
            'nurse_review' => 'Under Nurse Review',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'active' => 'Active',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'reassigned' => 'Reassigned',
        ];
    }

    public static function getAssignmentTypes(): array
    {
        return [
            'single_nurse' => 'Single Nurse',
            'dual_nurse' => 'Dual Nurse',
            'team_care' => 'Team Care',
            'rotating_care' => 'Rotating Care',
            'emergency_assignment' => 'Emergency Assignment',
        ];
    }

    public static function getIntensityLevels(): array
    {
        return [
            'light' => 'Light Intensity',
            'moderate' => 'Moderate Intensity',
            'intensive' => 'Intensive Care',
            'critical' => 'Critical Care',
        ];
    }
}