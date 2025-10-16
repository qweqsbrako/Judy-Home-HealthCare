<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TwoFactorCode extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'two_factor_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'code',
        'method',
        'expires_at',
        'used_at',
        'verified_ip',
        'ip_address',
        'user_agent',
        'login_attempt_id',
        'failed_attempts'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'failed_attempts' => 'integer',
        'user_id' => 'integer'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'code'
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($twoFactorCode) {
            if (!$twoFactorCode->failed_attempts) {
                $twoFactorCode->failed_attempts = 0;
            }
        });
    }

    /**
     * Get the user that owns the two-factor code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unused codes.
     */
    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }

    /**
     * Scope a query to only include non-expired codes.
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include valid codes.
     */
    public function scopeValid($query)
    {
        return $query->unused()->notExpired();
    }

    /**
     * Scope a query to only include expired codes.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by method.
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope a query to only include codes with failed attempts under limit.
     */
    public function scopeUnderFailureLimit($query, int $limit = 3)
    {
        return $query->where('failed_attempts', '<', $limit);
    }

    /**
     * Check if the code has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Check if the code has been used.
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Check if the code is valid (not used, not expired, under failure limit).
     */
    public function isValid(int $failureLimit = 3): bool
    {
        return !$this->isUsed() 
            && !$this->isExpired() 
            && $this->failed_attempts < $failureLimit;
    }

    /**
     * Check if the code has exceeded failure attempts.
     */
    public function hasExceededFailureLimit(int $limit = 3): bool
    {
        return $this->failed_attempts >= $limit;
    }

    /**
     * Mark the code as used.
     */
    public function markAsUsed(string $ip = null): bool
    {
        return $this->update([
            'used_at' => now(),
            'verified_ip' => $ip
        ]);
    }

    /**
     * Increment failed attempts.
     */
    public function incrementFailedAttempts(): bool
    {
        return $this->increment('failed_attempts');
    }

    /**
     * Get the time remaining before expiration.
     */
    public function getTimeRemaining(): ?int
    {
        if (!$this->expires_at || $this->isExpired()) {
            return 0;
        }

        return now()->diffInSeconds($this->expires_at, false);
    }

    /**
     * Get the time remaining in minutes.
     */
    public function getTimeRemainingInMinutes(): ?int
    {
        $seconds = $this->getTimeRemaining();
        return $seconds ? ceil($seconds / 60) : 0;
    }

    /**
     * Get formatted time remaining.
     */
    public function getFormattedTimeRemaining(): string
    {
        $seconds = $this->getTimeRemaining();
        
        if ($seconds <= 0) {
            return 'Expired';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$remainingSeconds}s";
    }

    /**
     * Clean up expired codes.
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Clean up old codes (older than 1 hour).
     */
    public static function cleanupOld(): int
    {
        return static::where('created_at', '<', now()->subHour())->delete();
    }

    /**
     * Find valid code by user and code value.
     */
    public static function findValidCodeForUser(int $userId): ?self
    {
        return static::byUser($userId)
            ->valid()
            ->underFailureLimit()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Delete all codes for a user.
     */
    public static function deleteForUser(int $userId): int
    {
        return static::where('user_id', $userId)->delete();
    }

    /**
     * Get delivery method display name.
     */
    public function getMethodDisplayName(): string
    {
        return match($this->method) {
            'email' => 'Email',
            'sms' => 'SMS',
            'voice' => 'Voice Call',
            default => 'Unknown'
        };
    }

    /**
     * Check if this is an email-based code.
     */
    public function isEmailCode(): bool
    {
        return $this->method === 'email';
    }

    /**
     * Check if this is an SMS-based code.
     */
    public function isSmsCode(): bool
    {
        return $this->method === 'sms';
    }

    /**
     * Check if this is a voice-based code.
     */
    public function isVoiceCode(): bool
    {
        return $this->method === 'voice';
    }
}