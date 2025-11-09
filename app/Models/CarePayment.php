<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CarePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'care_request_id',
        'patient_id',
        'payment_type',
        'amount',
        'currency',
        'tax_amount',
        'total_amount',
        'payment_method',
        'payment_provider',
        'transaction_id',
        'reference_number',
        'provider_reference',
        'status',
        'description',
        'failure_reason',
        'metadata',
        'paid_at',
        'refunded_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_status',
        'is_paid',
        'is_pending',
        'is_expired',
    ];

    /**
     * Relationships
     */
    public function careRequest(): BelongsTo
    {
        return $this->belongsTo(CareRequest::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Accessors
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && !$this->is_paid;
    }

    /**
     * Scopes
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->reference_number) {
                $payment->reference_number = static::generateReferenceNumber();
            }
            
            // Set expiry time (30 minutes for pending payments)
            if (!$payment->expires_at) {
                $payment->expires_at = now()->addMinutes(30);
            }
        });
    }

    /**
     * Generate unique reference number
     */
    public static function generateReferenceNumber(): string
    {
        do {
            $reference = 'PAY-' . strtoupper(Str::random(12));
        } while (static::where('reference_number', $reference)->exists());

        return $reference;
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(string $transactionId, array $metadata = []): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'paid_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Initiate refund
     */
    public function refund(): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }

    /**
     * Check if payment was created by admin
     */
    public function isAdminCreated(): bool
    {
        return $this->payment_provider === 'admin_created' || 
            ($this->metadata && isset($this->metadata['created_by_admin']));
    }

    /**
     * Get admin who created the payment
     */
    public function getAdminCreator(): ?string
    {
        if ($this->metadata && isset($this->metadata['admin_name'])) {
            return $this->metadata['admin_name'];
        }
        
        if ($this->metadata && isset($this->metadata['created_by_admin'])) {
            $admin = User::find($this->metadata['created_by_admin']);
            return $admin ? $admin->first_name . ' ' . $admin->last_name : null;
        }
        
        return null;
    }
}