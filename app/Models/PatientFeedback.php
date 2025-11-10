<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patient_feedback';

    protected $fillable = [
        'patient_id',
        'nurse_id',
        'schedule_id',
        'rating',
        'feedback_text',
        'would_recommend',
        'care_date',
        'status',
        'response_text',
        'responded_by',
        'responded_at',
        'admin_response',
        'response_date'
    ];

    protected $casts = [
        'rating' => 'integer',
        'would_recommend' => 'boolean',
        'care_date' => 'date',
        'responded_at' => 'datetime',
        'response_date' => 'datetime',  
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships

    /**
     * Get the patient who gave this feedback
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the nurse this feedback is about
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    /**
     * Get the schedule this feedback relates to
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Get the user who responded to this feedback
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    // Scopes

    /**
     * Scope for pending feedback (not yet responded)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for responded feedback
     */
    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    /**
     * Scope for feedback with specific rating
     */
    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for feedback about specific nurse
     */
    public function scopeForNurse($query, $nurseId)
    {
        return $query->where('nurse_id', $nurseId);
    }

    /**
     * Scope for feedback from specific patient
     */
    public function scopeFromPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope for feedback within date range
     */
    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        $query->whereDate('care_date', '>=', $startDate);
        if ($endDate) {
            $query->whereDate('care_date', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope for high ratings (4-5 stars)
     */
    public function scopeHighRatings($query)
    {
        return $query->whereIn('rating', [4, 5]);
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    /**
     * Scope for low ratings (1-2 stars)
     */
    public function scopeLowRatings($query)
    {
        return $query->whereIn('rating', [1, 2]);
    }

    /**
     * Scope for recent feedback
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors

    /**
     * Get the rating as stars display
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get the feedback display text with truncation
     */
    public function getFeedbackDisplayAttribute(): string
    {
        return strlen($this->feedback_text) > 100 
            ? substr($this->feedback_text, 0, 97) . '...'
            : $this->feedback_text;
    }

    /**
     * Check if feedback has been responded to
     */
    public function getIsRespondedAttribute(): bool
    {
        return $this->status === 'responded' && !empty($this->response_text);
    }

    /**
     * Get days since feedback was submitted
     */
    public function getDaysSinceSubmissionAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get the overall sentiment based on rating
     */
    public function getSentimentAttribute(): string
    {
        return match($this->rating) {
            1, 2 => 'negative',
            3 => 'neutral',
            4, 5 => 'positive',
            default => 'unknown'
        };
    }

    // Mutators

    /**
     * Ensure rating is within valid range
     */
    public function setRatingAttribute($value)
    {
        $this->attributes['rating'] = max(1, min(5, (int) $value));
    }

    /**
     * Clean and sanitize feedback text
     */
    public function setFeedbackTextAttribute($value)
    {
        $this->attributes['feedback_text'] = trim(strip_tags($value));
    }

    /**
     * Clean and sanitize response text
     */
    public function setResponseTextAttribute($value)
    {
        $this->attributes['response_text'] = $value ? trim(strip_tags($value)) : null;
    }

    // Static methods

    /**
     * Get average rating for a nurse
     */
    public static function averageRatingForNurse($nurseId, $startDate = null, $endDate = null)
    {
        $query = static::where('nurse_id', $nurseId);
        
        if ($startDate) {
            $query->whereDate('care_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('care_date', '<=', $endDate);
        }
        
        return round($query->avg('rating'), 2);
    }

    /**
     * Get feedback statistics for a nurse
     */
    public static function nurseStatistics($nurseId, $startDate = null, $endDate = null)
    {
        $query = static::where('nurse_id', $nurseId);
        
        if ($startDate) {
            $query->whereDate('care_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('care_date', '<=', $endDate);
        }
        
        $feedback = $query->get();
        
        return [
            'total_feedback' => $feedback->count(),
            'average_rating' => round($feedback->avg('rating'), 2),
            'rating_distribution' => [
                '5_star' => $feedback->where('rating', 5)->count(),
                '4_star' => $feedback->where('rating', 4)->count(),
                '3_star' => $feedback->where('rating', 3)->count(),
                '2_star' => $feedback->where('rating', 2)->count(),
                '1_star' => $feedback->where('rating', 1)->count(),
            ],
            'positive_feedback_rate' => $feedback->count() > 0 
                ? round(($feedback->whereIn('rating', [4, 5])->count() / $feedback->count()) * 100, 1)
                : 0,
            'response_rate' => $feedback->count() > 0 
                ? round(($feedback->where('status', 'responded')->count() / $feedback->count()) * 100, 1)
                : 0
        ];
    }

    /**
     * Get overall satisfaction metrics
     */
    public static function satisfactionMetrics($startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate) {
            $query->whereDate('care_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('care_date', '<=', $endDate);
        }
        
        $feedback = $query->get();
        
        return [
            'total_responses' => $feedback->count(),
            'average_rating' => round($feedback->avg('rating'), 2),
            'satisfaction_rate' => $feedback->count() > 0 
                ? round(($feedback->whereIn('rating', [4, 5])->count() / $feedback->count()) * 100, 1)
                : 0,
            'recommendation_rate' => $feedback->count() > 0 
                ? round(($feedback->where('would_recommend', true)->count() / $feedback->count()) * 100, 1)
                : 0,
            'response_rate' => $feedback->count() > 0 
                ? round(($feedback->where('status', 'responded')->count() / $feedback->count()) * 100, 1)
                : 0,
            'pending_responses' => $feedback->where('status', 'pending')->count()
        ];
    }

    // Model events
    protected static function boot()
    {
        parent::boot();

        // Automatically set care_date to today if not provided
        static::creating(function ($feedback) {
            if (empty($feedback->care_date)) {
                $feedback->care_date = now()->toDateString();
            }
        });

        // Update status when response is added - handle both field sets
        static::updating(function ($feedback) {
            // Sync the duplicate fields
            if (!empty($feedback->admin_response) && empty($feedback->response_text)) {
                $feedback->response_text = $feedback->admin_response;
            }
            if (!empty($feedback->response_text) && empty($feedback->admin_response)) {
                $feedback->admin_response = $feedback->response_text;
            }
            
            if (!empty($feedback->response_date) && empty($feedback->responded_at)) {
                $feedback->responded_at = $feedback->response_date;
            }
            if (!empty($feedback->responded_at) && empty($feedback->response_date)) {
                $feedback->response_date = $feedback->responded_at;
            }
            
            // Update status automatically
            if ((!empty($feedback->response_text) || !empty($feedback->admin_response)) && $feedback->status !== 'responded') {
                $feedback->status = 'responded';
                
                if (empty($feedback->responded_at) && empty($feedback->response_date)) {
                    $feedback->responded_at = now();
                    $feedback->response_date = now();
                }
            }
        });
    }
}