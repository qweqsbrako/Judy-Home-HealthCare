<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class IncidentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        // Section 1: General Information
        'report_date',
        'incident_date',
        'incident_time',
        'incident_location',
        'incident_type',
        'incident_type_other',
        
        // Section 2: Person(s) Involved
        'patient_id',
        'patient_age',
        'patient_sex',
        'client_id_case_no',
        'staff_family_involved',
        'staff_family_role',
        'staff_family_role_other',
        
        // Section 3: Description
        'incident_description',
        
        // Section 4: Immediate Actions
        'first_aid_provided',
        'first_aid_description',
        'care_provider_name',
        'transferred_to_hospital',
        'hospital_transfer_details',
        
        // Section 5: Witnesses
        'witness_names',
        'witness_contacts',
        
        // Section 6: Follow-Up
        'reported_to_supervisor',
        'corrective_preventive_actions',
        
        // Section 7: Reporting & Review
        'reported_by',
        'reported_at',
        'reviewed_by',
        'reviewed_at',
        
        // Additional tracking
        'status',
        'severity',
        'follow_up_required',
        'follow_up_date',
        'assigned_to',
        'attachments',
        'investigation_notes',
        'final_resolution',
        'prevention_measures',
    ];

    protected $casts = [
        'report_date' => 'date',
        'incident_date' => 'date',
        'incident_time' => 'datetime:H:i',
        'reported_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'follow_up_date' => 'date',
        'first_aid_provided' => 'boolean',
        'transferred_to_hospital' => 'boolean',
        'follow_up_required' => 'boolean',
        'attachments' => 'array',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * The patient involved in the incident
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * The user who reported this incident
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * The user who reviewed this incident
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The user assigned to handle this incident
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get nurse involved (if staff_family_role is 'nurse')
     * This is a computed relationship since nurse isn't directly stored
     */
    public function getNurseAttribute()
    {
        if ($this->staff_family_role === 'nurse' && $this->staff_family_involved) {
            // Try to find nurse by name - this is not ideal but based on current structure
            return User::where('role', 'nurse')
                      ->where(function($query) {
                          $names = explode(' ', $this->staff_family_involved);
                          if (count($names) >= 2) {
                              $query->where('first_name', 'like', '%' . $names[0] . '%')
                                    ->where('last_name', 'like', '%' . $names[1] . '%');
                          } else {
                              $query->where('first_name', 'like', '%' . $this->staff_family_involved . '%')
                                    ->orWhere('last_name', 'like', '%' . $this->staff_family_involved . '%');
                          }
                      })
                      ->first();
        }
        return null;
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeByType($query, $type)
    {
        return $query->where('incident_type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeRequiresFollowUp($query)
    {
        return $query->where('follow_up_required', true);
    }

    public function scopeOverdueFollowUp($query)
    {
        return $query->where('follow_up_required', true)
                    ->where('follow_up_date', '<', now())
                    ->whereNotIn('status', ['resolved', 'closed']);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('incident_date', '>=', now()->subDays($days));
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByReporter($query, $reporterId)
    {
        return $query->where('reported_by', $reporterId);
    }

    public function scopeInvolvingNurse($query, $nurseName)
    {
        return $query->where('staff_family_role', 'nurse')
                    ->where('staff_family_involved', 'like', "%{$nurseName}%");
    }

    // ============================================
    // ACCESSORS & HELPER METHODS
    // ============================================

    public function getFormattedIncidentDateTimeAttribute(): string
    {
        return $this->incident_date->format('M j, Y') . ' at ' . 
               Carbon::parse($this->incident_time)->format('g:i A');
    }

    public function getFormattedIncidentTypeAttribute(): string
    {
        if ($this->incident_type === 'other' && $this->incident_type_other) {
            return ucfirst($this->incident_type_other);
        }
        
        return str_replace('_', ' ', ucfirst($this->incident_type));
    }

    public function getFormattedSeverityAttribute(): string
    {
        return ucfirst($this->severity);
    }

    public function getFormattedStatusAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->follow_up_required && 
               $this->follow_up_date && 
               $this->follow_up_date->isPast() &&
               !in_array($this->status, ['resolved', 'closed']);
    }

    public function getIsCriticalAttribute(): bool
    {
        return $this->severity === 'critical';
    }

    public function getRequiresAttentionAttribute(): bool
    {
        return $this->severity === 'critical' || 
               $this->status === 'pending' || 
               $this->is_overdue;
    }

    public function getDaysOldAttribute(): int
    {
        return $this->incident_date->diffInDays(now());
    }

    public function getHasWitnessesAttribute(): bool
    {
        return !empty($this->witness_names);
    }

    public function getInvolvedStaffAttribute(): ?string
    {
        if ($this->staff_family_involved && $this->staff_family_role) {
            $role = $this->staff_family_role === 'other' ? $this->staff_family_role_other : $this->staff_family_role;
            return $this->staff_family_involved . ' (' . ucfirst($role) . ')';
        }
        return $this->staff_family_involved;
    }

    // ============================================
    // METHODS
    // ============================================

    /**
     * Mark incident as reviewed
     */
    public function markAsReviewed(User $reviewer, string $notes = null): bool
    {
        return $this->update([
            'status' => 'under_review',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'investigation_notes' => $notes,
        ]);
    }

    /**
     * Resolve incident
     */
    public function resolve(User $resolver, string $resolution, string $preventionMeasures = null): bool
    {
        return $this->update([
            'status' => 'resolved',
            'final_resolution' => $resolution,
            'prevention_measures' => $preventionMeasures,
            'reviewed_by' => $resolver->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Close incident
     */
    public function close(User $closer): bool
    {
        return $this->update([
            'status' => 'closed',
            'reviewed_by' => $closer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Assign incident to user
     */
    public function assignTo(User $user): bool
    {
        return $this->update([
            'assigned_to' => $user->id,
            'status' => $this->status === 'pending' ? 'under_review' : $this->status,
        ]);
    }

    /**
     * Add follow-up requirement
     */
    public function requireFollowUp(Carbon $date): bool
    {
        return $this->update([
            'follow_up_required' => true,
            'follow_up_date' => $date,
        ]);
    }

    /**
     * Format for API responses
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'report_date' => $this->report_date->format('Y-m-d'),
            'incident_date' => $this->incident_date->format('Y-m-d'),
            'incident_time' => Carbon::parse($this->incident_time)->format('H:i'),
            'incident_location' => $this->incident_location,
            'incident_type' => $this->formatted_incident_type,
            'incident_description' => $this->incident_description,
            'patient_name' => $this->patient ? $this->patient->full_name : 'N/A',
            'patient_id' => $this->patient_id,
            'involved_staff' => $this->involved_staff,
            'reporter_name' => $this->reporter->full_name,
            'reported_at' => $this->reported_at->format('Y-m-d H:i'),
            'reviewer_name' => $this->reviewer ? $this->reviewer->full_name : null,
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i'),
            'status' => $this->formatted_status,
            'severity' => $this->formatted_severity,
            'first_aid_provided' => $this->first_aid_provided,
            'transferred_to_hospital' => $this->transferred_to_hospital,
            'follow_up_required' => $this->follow_up_required,
            'follow_up_date' => $this->follow_up_date?->format('Y-m-d'),
            'is_overdue' => $this->is_overdue,
            'is_critical' => $this->is_critical,
            'requires_attention' => $this->requires_attention,
            'days_old' => $this->days_old,
            'has_witnesses' => $this->has_witnesses,
            'corrective_actions' => $this->corrective_preventive_actions,
            'investigation_notes' => $this->investigation_notes,
            'final_resolution' => $this->final_resolution,
            'attachments' => $this->attachments,
        ];
    }

    // ============================================
    // STATIC METHODS
    // ============================================

    /**
     * Get incident types
     */
    public static function getIncidentTypes(): array
    {
        return [
            'fall' => 'Fall',
            'medication_error' => 'Medication Error',
            'equipment_failure' => 'Equipment Failure',
            'injury' => 'Injury',
            'other' => 'Other',
        ];
    }

    /**
     * Get severity levels
     */
    public static function getSeverityLevels(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'under_review' => 'Under Review',
            'investigated' => 'Investigated',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    /**
     * Get incident statistics
     */
    public static function getStats($dateFrom = null): array
    {
        $dateFrom = $dateFrom ?: Carbon::now()->subDays(30);
        
        $query = static::where('incident_date', '>=', $dateFrom);
        
        return [
            'total_incidents' => $query->count(),
            'critical_incidents' => $query->clone()->where('severity', 'critical')->count(),
            'pending_incidents' => $query->clone()->where('status', 'pending')->count(),
            'overdue_follow_ups' => static::overdueFollowUp()->count(),
            'incidents_by_type' => $query->clone()
                ->selectRaw('incident_type, COUNT(*) as count')
                ->groupBy('incident_type')
                ->pluck('count', 'incident_type')
                ->toArray(),
            'incidents_by_severity' => $query->clone()
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
        ];
    }

    /**
     * Get recent critical incidents
     */
    public static function getRecentCritical($limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['patient', 'reporter'])
                    ->where('severity', 'critical')
                    ->latest('incident_date')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Calculate incident rate for a period
     */
    public static function calculateIncidentRate($dateFrom, $dateTo = null): float
    {
        $dateTo = $dateTo ?: Carbon::now();
        $days = $dateFrom->diffInDays($dateTo);
        
        $incidentCount = static::whereBetween('incident_date', [$dateFrom, $dateTo])->count();
        $activeCarePlans = CarePlan::where('status', 'active')->count();
        
        if ($activeCarePlans === 0) return 0;
        
        return round($incidentCount / $activeCarePlans, 3);
    }

    /**
     * Get incidents requiring attention
     */
    public static function getRequiringAttention(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['patient', 'reporter', 'assignedTo'])
                    ->where(function($query) {
                        $query->where('severity', 'critical')
                              ->orWhere('status', 'pending')
                              ->orWhere(function($subQuery) {
                                  $subQuery->where('follow_up_required', true)
                                          ->where('follow_up_date', '<', now())
                                          ->whereNotIn('status', ['resolved', 'closed']);
                              });
                    })
                    ->orderByRaw("
                        CASE severity
                            WHEN 'critical' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'medium' THEN 3
                            WHEN 'low' THEN 4
                        END
                    ")
                    ->orderBy('incident_date', 'desc')
                    ->get();
    }
}