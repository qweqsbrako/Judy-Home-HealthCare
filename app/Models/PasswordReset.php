<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PasswordReset extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'password_resets';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'token',
        'created_at',
        'expires_at',
        'used_at',
        'used_ip',
        'ip_address',
        'user_agent'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($passwordReset) {
            if (!$passwordReset->created_at) {
                $passwordReset->created_at = now();
            }
        });
    }

    /**
     * Scope a query to only include unused tokens.
     */
    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }

    /**
     * Scope a query to only include non-expired tokens.
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include valid tokens.
     */
    public function scopeValid($query)
    {
        return $query->unused()->notExpired();
    }

    /**
     * Scope a query to only include expired tokens.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to filter by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Get the user associated with this password reset.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Check if the token has been used.
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Check if the token is valid (not used and not expired).
     */
    public function isValid(): bool
    {
        return !$this->isUsed() && !$this->isExpired();
    }

    /**
     * Mark the token as used.
     */
    public function markAsUsed(string $ip = null): bool
    {
        return $this->update([
            'used_at' => now(),
            'used_ip' => $ip
        ]);
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
     * Clean up expired tokens.
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Clean up old tokens (older than 24 hours).
     */
    public static function cleanupOld(): int
    {
        return static::where('created_at', '<', now()->subHours(24))->delete();
    }

    /**
     * Find valid token by email and token.
     */
    public static function findValidToken(string $email, string $token): ?self
    {
        return static::byEmail($email)
            ->valid()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Delete all tokens for an email.
     */
    public static function deleteForEmail(string $email): int
    {
        return static::where('email', $email)->delete();
    }
}