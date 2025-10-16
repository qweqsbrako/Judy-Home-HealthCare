<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ProgressNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'nurse_id',
        'visit_date',
        'visit_time',
        'vitals',
        'interventions',
        'general_condition',
        'pain_level',
        'wound_status',
        'other_observations',
        'education_provided',
        'family_concerns',
        'next_steps',
        'signed_at',
        'signature_method'
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visit_time' => 'datetime:H:i',
        'vitals' => 'array',
        'interventions' => 'array',
        'pain_level' => 'integer',
        'signed_at' => 'datetime',
    ];

    protected $appends = [
        'patient_name',
        'nurse_name',
        'patient_avatar_url',
        'nurse_license'
    ];

    /**
     * Validation rules for progress notes
     */
    public static function validationRules($isUpdate = false)
    {
        $rules = [
            'patient_id' => 'required|exists:users,id',
            'nurse_id' => 'required|exists:users,id',
            'visit_date' => 'required|date',
            'visit_time' => 'required|date_format:H:i',
            'general_condition' => 'required|in:improved,stable,deteriorating',
            'pain_level' => 'required|integer|min:0|max:10',
            'vitals' => 'nullable|array',
            'vitals.temperature' => 'nullable|numeric|min:30|max:45',
            'vitals.pulse' => 'nullable|integer|min:30|max:200',
            'vitals.respiration' => 'nullable|integer|min:8|max:40',
            'vitals.blood_pressure' => 'nullable|string|max:20',
            'vitals.spo2' => 'nullable|integer|min:70|max:100',
            'interventions' => 'nullable|array',
            'wound_status' => 'nullable|string|max:1000',
            'other_observations' => 'nullable|string|max:2000',
            'education_provided' => 'nullable|string|max:1000',
            'family_concerns' => 'nullable|string|max:1000',
            'next_steps' => 'nullable|string|max:2000',
        ];

        return $rules;
    }

    /**
     * Custom validation messages
     */
    public static function validationMessages()
    {
        return [
            'patient_id.required' => 'Patient selection is required.',
            'patient_id.exists' => 'Selected patient does not exist.',
            'nurse_id.required' => 'Nurse selection is required.',
            'nurse_id.exists' => 'Selected nurse does not exist.',
            'visit_date.required' => 'Visit date is required.',
            'visit_time.required' => 'Visit time is required.',
            'general_condition.required' => 'General condition is required.',
            'general_condition.in' => 'General condition must be: improved, stable, or deteriorating.',
            'pain_level.required' => 'Pain level is required.',
            'pain_level.min' => 'Pain level must be between 0 and 10.',
            'pain_level.max' => 'Pain level must be between 0 and 10.',
            'vitals.temperature.min' => 'Temperature must be between 30°C and 45°C.',
            'vitals.temperature.max' => 'Temperature must be between 30°C and 45°C.',
            'vitals.pulse.min' => 'Pulse must be between 30 and 200 bpm.',
            'vitals.pulse.max' => 'Pulse must be between 30 and 200 bpm.',
            'vitals.respiration.min' => 'Respiration must be between 8 and 40 per minute.',
            'vitals.respiration.max' => 'Respiration must be between 8 and 40 per minute.',
            'vitals.spo2.min' => 'SpO₂ must be between 70% and 100%.',
            'vitals.spo2.max' => 'SpO₂ must be between 70% and 100%.',
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

    public function getPatientAvatarUrlAttribute(): string
    {
        return $this->patient && $this->patient->avatar 
            ? asset('storage/' . $this->patient->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->patient_name) . '&color=667eea&background=f8f9fa&size=200&font-size=0.6';
    }

    public function getNurseLicenseAttribute(): ?string
    {
        return $this->nurse ? $this->nurse->license_number : null;
    }

    /**
     * Scopes
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForNurse($query, $nurseId)
    {
        return $query->where('nurse_id', $nurseId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('visit_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('visit_date', [$startDate, $endDate]);
    }

    public function scopeCreatedOnDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59'
        ]);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('general_condition', $condition);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('patient', function ($patientQuery) use ($search) {
                $patientQuery->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->orWhereHas('nurse', function ($nurseQuery) use ($search) {
                $nurseQuery->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->orWhere('other_observations', 'like', "%{$search}%")
            ->orWhere('next_steps', 'like', "%{$search}%")
            ->orWhere('education_provided', 'like', "%{$search}%")
            ->orWhere('family_concerns', 'like', "%{$search}%");
        });
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeRecentVisits($query, $days = 30)
    {
        return $query->where('visit_date', '>=', Carbon::now()->subDays($days));
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('visit_date', 'desc')
                    ->orderBy('visit_time', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Helper methods
     */
    public function hasAnyInterventions(): bool
    {
        if (!$this->interventions) {
            return false;
        }

        $interventionFlags = [
            'medication_administered',
            'wound_care',
            'physiotherapy',
            'nutrition_support',
            'hygiene_care',
            'counseling_education',
            'other'
        ];

        foreach ($interventionFlags as $flag) {
            if (isset($this->interventions[$flag]) && $this->interventions[$flag] === true) {
                return true;
            }
        }

        return false;
    }

    public function getInterventionsList(): array
    {
        $interventions = [];
        
        if (!$this->interventions) {
            return $interventions;
        }

        $interventionMap = [
            'medication_administered' => 'Medication Administered',
            'wound_care' => 'Wound Care',
            'physiotherapy' => 'Physiotherapy/Exercise',
            'nutrition_support' => 'Nutrition/Feeding Support',
            'hygiene_care' => 'Hygiene/Personal Care',
            'counseling_education' => 'Counseling/Education',
            'other' => 'Other Interventions'
        ];

        foreach ($interventionMap as $key => $label) {
            if (isset($this->interventions[$key]) && $this->interventions[$key] === true) {
                $detailsKey = str_replace('_administered', '_details', $key . '_details');
                $detailsKey = str_replace('_care_details', '_details', $detailsKey);
                
                $interventions[] = [
                    'type' => $label,
                    'details' => $this->interventions[$detailsKey] ?? 'No details provided'
                ];
            }
        }

        return $interventions;
    }

    public function getPainLevelDescription(): string
    {
        $descriptions = [
            0 => 'No pain',
            1 => 'Mild pain',
            2 => 'Mild pain',
            3 => 'Mild pain',
            4 => 'Moderate pain',
            5 => 'Moderate pain',
            6 => 'Moderate pain',
            7 => 'Severe pain',
            8 => 'Severe pain',
            9 => 'Very severe pain',
            10 => 'Worst possible pain'
        ];

        return $descriptions[$this->pain_level] ?? 'Unknown';
    }

    public function hasAbnormalVitals(): bool
    {
        if (!$this->vitals) {
            return false;
        }

        // Define normal ranges
        $normalRanges = [
            'temperature' => [35.5, 37.5], // Celsius
            'pulse' => [60, 100], // BPM
            'respiration' => [12, 20], // Per minute
            'spo2' => [95, 100] // Percentage
        ];

        foreach ($normalRanges as $vital => $range) {
            if (isset($this->vitals[$vital]) && is_numeric($this->vitals[$vital])) {
                $value = (float) $this->vitals[$vital];
                if ($value < $range[0] || $value > $range[1]) {
                    return true;
                }
            }
        }

        return false;
    }

    public function signNote($method = 'digital'): void
    {
        $this->update([
            'signed_at' => now(),
            'signature_method' => $method
        ]);
    }

    public function isSigned(): bool
    {
        return !is_null($this->signed_at);
    }

    /**
     * Boot method to automatically sign notes when created
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($progressNote) {
            // Automatically sign the note when created
            $progressNote->signed_at = now();
            $progressNote->signature_method = 'digital';
        });
    }
}