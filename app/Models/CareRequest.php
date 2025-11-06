<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CareRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'assigned_nurse_id',
        'medical_assessment_id',
        'care_type',
        'urgency_level',
        'description',
        'special_requirements',
        'preferred_language',
        'preferred_start_date',
        'preferred_time',
        'service_address',
        'city',
        'region',
        'latitude',
        'longitude',
        'status',
        'rejection_reason',
        'admin_notes',
        'assessment_scheduled_at',
        'assessment_completed_at',
        'care_started_at',
        'care_ended_at',
        'cancelled_at',
    ];

    protected $casts = [
        'preferred_start_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'assessment_scheduled_at' => 'datetime',
        'assessment_completed_at' => 'datetime',
        'care_started_at' => 'datetime',
        'care_ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_status',
        'can_cancel',
        'requires_assessment_payment',
        'requires_care_payment',
    ];

    /**
     * Relationships
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function assignedNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    public function medicalAssessment(): BelongsTo
    {
        return $this->belongsTo(MedicalAssessment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CarePayment::class);
    }

    public function assessmentPayment(): HasOne
    {
        return $this->hasOne(CarePayment::class)
            ->where('payment_type', 'assessment_fee')
            ->latest();
    }

    public function carePayment(): HasOne
    {
        return $this->hasOne(CarePayment::class)
            ->where('payment_type', 'care_fee')
            ->latest();
    }

    /**
     * Accessors
     */
    public function getFormattedStatusAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getCanCancelAttribute(): bool
    {
        return in_array($this->status, [
            'pending_payment',
            'payment_received',
            'nurse_assigned',
            'assessment_scheduled'
        ]);
    }

    public function getRequiresAssessmentPaymentAttribute(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function getRequiresCarePaymentAttribute(): bool
    {
        return $this->status === 'awaiting_care_payment';
    }

    /**
     * Scopes
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            'payment_received',
            'nurse_assigned',
            'assessment_scheduled',
            'assessment_completed',
            'under_review',
            'care_plan_created',
            'care_active'
        ]);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Helper Methods
     */
    public function hasCompletedAssessmentPayment(): bool
    {
        return $this->assessmentPayment()
            ->where('status', 'completed')
            ->exists();
    }

    public function hasCompletedCarePayment(): bool
    {
        return $this->carePayment()
            ->where('status', 'completed')
            ->exists();
    }

    public function assignNurse(int $nurseId): void
    {
        $this->update([
            'assigned_nurse_id' => $nurseId,
            'status' => 'nurse_assigned'
        ]);
    }

    public function scheduleAssessment(string $dateTime): void
    {
        $this->update([
            'assessment_scheduled_at' => $dateTime,
            'status' => 'assessment_scheduled'
        ]);
    }

    public function completeAssessment(int $assessmentId): void
    {
        $this->update([
            'medical_assessment_id' => $assessmentId,
            'assessment_completed_at' => now(),
            'status' => 'assessment_completed'
        ]);
    }

    public function markUnderReview(): void
    {
        $this->update(['status' => 'under_review']);
    }

    public function createCarePlan(): void
    {
        $this->update(['status' => 'care_plan_created']);
    }

    public function awaitCarePayment(): void
    {
        $this->update(['status' => 'awaiting_care_payment']);
    }

    public function startCare(): void
    {
        $this->update([
            'care_started_at' => now(),
            'status' => 'care_active'
        ]);
    }

    public function completeCare(): void
    {
        $this->update([
            'care_ended_at' => now(),
            'status' => 'care_completed'
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'rejection_reason' => $reason,
            'cancelled_at' => now()
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'care_type' => 'required|in:general_nursing,elderly_care,post_surgical,chronic_disease,palliative_care,rehabilitation,wound_care,medication_management',
            'urgency_level' => 'sometimes|in:routine,urgent,emergency',
            'description' => 'required|string|min:20|max:1000',
            'special_requirements' => 'nullable|string|max:500',
            'preferred_language' => 'nullable|string|max:50',
            'preferred_start_date' => 'nullable|date|after_or_equal:today',
            'preferred_time' => 'nullable|in:morning,afternoon,evening,night,anytime',
            'service_address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'care_type.required' => 'Please select the type of care needed.',
            'description.required' => 'Please provide a description of your care needs.',
            'description.min' => 'Description must be at least 20 characters.',
            'service_address.required' => 'Service address is required.',
            'preferred_start_date.after_or_equal' => 'Start date cannot be in the past.',
        ];
    }
}