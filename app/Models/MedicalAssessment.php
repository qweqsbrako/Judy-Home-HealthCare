<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MedicalAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'nurse_id',
        'physical_address',
        'occupation',
        'religion',
        'emergency_contact_1_name',
        'emergency_contact_1_relationship',
        'emergency_contact_1_phone',
        'emergency_contact_2_name',
        'emergency_contact_2_relationship',
        'emergency_contact_2_phone',
        'presenting_condition',
        'past_medical_history',
        'allergies',
        'current_medications',
        'special_needs',
        'general_condition',
        'hydration_status',
        'nutrition_status',
        'mobility_status',
        'has_wounds',
        'wound_description',
        'pain_level',
        'initial_vitals',
        'initial_nursing_impression',
        'assessment_status',
        'completed_at'
    ];

    protected $casts = [
        'initial_vitals' => 'array',
        'has_wounds' => 'boolean',
        'pain_level' => 'integer',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'patient_name',
        'nurse_name',
        'patient_full_info',
        'assessment_summary'
    ];

    /**
     * Validation rules for medical assessments
     */
    public static function validationRules($isUpdate = false)
    {
        $rules = [
            // Patient information (for creating new patient)
            'patient_first_name' => 'required_without:patient_id|string|max:255',
            'patient_last_name' => 'required_without:patient_id|string|max:255',
            //'patient_age' => 'required_without:patient_id|integer|min:0|max:120',
            'patient_gender' => 'required_without:patient_id|in:male,female,other',
            'patient_phone' => 'required_without:patient_id|string|max:20',
            'patient_date_of_birth' => 'required_without:patient_id|date|before:today',
            'patient_ghana_card' => 'required_without:patient_id|string|max:20|unique:users,ghana_card_number',
            
            // Or existing patient
            'patient_id' => 'required_without:patient_first_name|exists:users,id',
            
            // Nurse performing assessment
            'nurse_id' => 'required|exists:users,id',
            
            // Extended client information
            'physical_address' => 'required|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            
            // Emergency contacts
            'emergency_contact_1_name' => 'required|string|max:255',
            'emergency_contact_1_relationship' => 'required|string|max:100',
            'emergency_contact_1_phone' => 'required|string|max:20',
            'emergency_contact_2_name' => 'nullable|string|max:255',
            'emergency_contact_2_relationship' => 'nullable|string|max:100',
            'emergency_contact_2_phone' => 'nullable|string|max:20',
            
            // Medical history
            'presenting_condition' => 'required|string|max:1000',
            'past_medical_history' => 'nullable|string|max:2000',
            'allergies' => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
            'special_needs' => 'nullable|string|max:1000',
            
            // Assessment
            'general_condition' => 'required|in:stable,unstable',
            'hydration_status' => 'required|in:adequate,dehydrated',
            'nutrition_status' => 'required|in:adequate,malnourished',
            'mobility_status' => 'required|in:independent,assisted,bedridden',
            'has_wounds' => 'required|boolean',
            'wound_description' => 'required_if:has_wounds,true|nullable|string|max:1000',
            'pain_level' => 'required|integer|min:0|max:10',
            
            // Vital signs
            'initial_vitals' => 'required|array',
            'initial_vitals.temperature' => 'required|numeric|min:30|max:45',
            'initial_vitals.pulse' => 'required|integer|min:30|max:200',
            'initial_vitals.respiratory_rate' => 'required|integer|min:8|max:40',
            'initial_vitals.blood_pressure' => 'required|string|max:20',
            'initial_vitals.spo2' => 'required|integer|min:70|max:100',
            'initial_vitals.weight' => 'required|numeric|min:1|max:500',
            
            // Nursing impression
            'initial_nursing_impression' => 'required|string|min:10|max:2000',
        ];

        return $rules;
    }

    /**
     * Custom validation messages
     */
    public static function validationMessages()
    {
        return [
            'patient_first_name.required_without' => 'Patient first name is required when creating a new patient.',
            'patient_ghana_card.unique' => 'A patient with this Ghana Card number already exists.',
            'emergency_contact_1_name.required' => 'At least one emergency contact is required.',
            'presenting_condition.required' => 'Presenting condition/diagnosis is required.',
            'initial_vitals.required' => 'Initial vital signs are required.',
            'initial_vitals.temperature.min' => 'Temperature must be between 30°C and 45°C.',
            'initial_vitals.pulse.min' => 'Pulse must be between 30 and 200 bpm.',
            'wound_description.required_if' => 'Wound description is required when wounds are present.',
            'initial_nursing_impression.min' => 'Nursing impression must be at least 50 characters.',
        ];
    }

    /**
     * Relationships
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    /**
     * Accessors
     */
    public function getPatientNameAttribute(): string
    {
        return $this->patient ? $this->patient->first_name . ' ' . $this->patient->last_name : 'Unknown';
    }

    public function getNurseNameAttribute(): string
    {
        return $this->nurse ? $this->nurse->first_name . ' ' . $this->nurse->last_name : 'Unknown';
    }

    public function getPatientFullInfoAttribute(): array
    {
        return [
            'name' => $this->patient_name,
            'age' => $this->patient ? $this->calculateAge($this->patient->date_of_birth) : null,
            'gender' => $this->patient ? $this->patient->gender : null,
            'phone' => $this->patient ? $this->patient->phone : null,
            'ghana_card' => $this->patient ? $this->patient->ghana_card_number : null,
            'address' => $this->physical_address,
            'occupation' => $this->occupation,
            'religion' => $this->religion,
        ];
    }

    public function getAssessmentSummaryAttribute(): array
    {
        return [
            'condition' => $this->general_condition,
            'hydration' => $this->hydration_status,
            'nutrition' => $this->nutrition_status,
            'mobility' => $this->mobility_status,
            'pain_level' => $this->pain_level,
            'has_wounds' => $this->has_wounds,
            'vital_signs_normal' => $this->hasNormalVitals(),
        ];
    }

    /**
     * Scopes
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByNurse($query, $nurseId)
    {
        return $query->where('nurse_id', $nurseId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('assessment_status', $status);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('general_condition', $condition);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeCompleted($query)
    {
        return $query->where('assessment_status', 'completed');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('patient', function ($patientQuery) use ($search) {
                $patientQuery->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhere('ghana_card_number', 'like', "%{$search}%");
            })
            ->orWhereHas('nurse', function ($nurseQuery) use ($search) {
                $nurseQuery->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->orWhere('presenting_condition', 'like', "%{$search}%")
            ->orWhere('initial_nursing_impression', 'like', "%{$search}%");
        });
    }

    /**
     * Helper methods
     */
    public function calculateAge($dateOfBirth): int
    {
        return Carbon::parse($dateOfBirth)->age;
    }

    public function hasNormalVitals(): bool
    {
        if (!$this->initial_vitals) {
            return false;
        }

        $vitals = $this->initial_vitals;
        $normalRanges = [
            'temperature' => [35.5, 37.5],
            'pulse' => [60, 100],
            'respiratory_rate' => [12, 20],
            'spo2' => [95, 100]
        ];

        foreach ($normalRanges as $vital => $range) {
            if (isset($vitals[$vital]) && is_numeric($vitals[$vital])) {
                $value = (float) $vitals[$vital];
                if ($value < $range[0] || $value > $range[1]) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getRiskLevel(): string
    {
        $riskFactors = 0;

        // Check various risk factors
        if ($this->general_condition === 'unstable') $riskFactors += 3;
        if ($this->hydration_status === 'dehydrated') $riskFactors += 2;
        if ($this->nutrition_status === 'malnourished') $riskFactors += 2;
        if ($this->mobility_status === 'bedridden') $riskFactors += 2;
        if ($this->has_wounds) $riskFactors += 1;
        if ($this->pain_level >= 7) $riskFactors += 2;
        if (!$this->hasNormalVitals()) $riskFactors += 2;

        if ($riskFactors >= 6) return 'high';
        if ($riskFactors >= 3) return 'medium';
        return 'low';
    }

    public function getEmergencyContacts(): array
    {
        $contacts = [];

        if ($this->emergency_contact_1_name) {
            $contacts[] = [
                'name' => $this->emergency_contact_1_name,
                'relationship' => $this->emergency_contact_1_relationship,
                'phone' => $this->emergency_contact_1_phone,
                'primary' => true
            ];
        }

        if ($this->emergency_contact_2_name) {
            $contacts[] = [
                'name' => $this->emergency_contact_2_name,
                'relationship' => $this->emergency_contact_2_relationship,
                'phone' => $this->emergency_contact_2_phone,
                'primary' => false
            ];
        }

        return $contacts;
    }

    public function markCompleted(): void
    {
        $this->update([
            'assessment_status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function markReviewed(): void
    {
        $this->update([
            'assessment_status' => 'reviewed'
        ]);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assessment) {
            if (!$assessment->completed_at) {
                $assessment->completed_at = now();
            }
        });
    }
}