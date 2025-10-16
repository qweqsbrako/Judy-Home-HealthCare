<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserSession Model - app/Models/UserSession.php
 */
class UserSession extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token_id',
        'ip_address',
        'user_agent',
        'device_name',
        'logged_in_at',
        'logged_out_at',
        'last_activity',
        'is_current'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'last_activity' => 'datetime',
        'is_current' => 'boolean',
        'user_id' => 'integer',
        'token_id' => 'integer'
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'duration',
        'is_active',
        'device_info',
        'location_info'
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active sessions.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('logged_out_at');
    }

    /**
     * Scope a query to only include ended sessions.
     */
    public function scopeEnded($query)
    {
        return $query->whereNotNull('logged_out_at');
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include current session.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Get session duration.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->logged_in_at) {
            return null;
        }

        $endTime = $this->logged_out_at ?? now();
        return $this->logged_in_at->diffInSeconds($endTime);
    }

    /**
     * Check if session is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->logged_out_at === null;
    }

    /**
     * Get device information.
     */
    public function getDeviceInfoAttribute(): array
    {
        $userAgent = $this->user_agent ?? '';
        
        return [
            'device' => $this->parseDevice($userAgent),
            'browser' => $this->parseBrowser($userAgent),
            'platform' => $this->parsePlatform($userAgent),
            'device_name' => $this->device_name
        ];
    }

    /**
     * Get location information.
     */
    public function getLocationInfoAttribute(): array
    {
        // This would integrate with IP geolocation service
        return [
            'ip' => $this->ip_address,
            'country' => null, // Would be populated by geolocation
            'city' => null,    // Would be populated by geolocation
            'region' => null   // Would be populated by geolocation
        ];
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): string
    {
        $duration = $this->duration;
        
        if (!$duration) {
            return 'Unknown';
        }

        if ($duration < 60) {
            return "{$duration} seconds";
        }

        $minutes = floor($duration / 60);
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Parse device from user agent.
     */
    private function parseDevice(string $userAgent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPhone/', $userAgent)) return 'iPhone';
            if (preg_match('/iPad/', $userAgent)) return 'iPad';
            if (preg_match('/Android/', $userAgent)) return 'Android Device';
            return 'Mobile Device';
        }
        return 'Desktop';
    }

    /**
     * Parse browser from user agent.
     */
    private function parseBrowser(string $userAgent): string
    {
        if (preg_match('/Chrome/', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/', $userAgent)) return 'Safari';
        if (preg_match('/Edge/', $userAgent)) return 'Edge';
        return 'Unknown Browser';
    }

    /**
     * Parse platform from user agent.
     */
    private function parsePlatform(string $userAgent): string
    {
        if (preg_match('/Windows/', $userAgent)) return 'Windows';
        if (preg_match('/Macintosh/', $userAgent)) return 'macOS';
        if (preg_match('/Linux/', $userAgent)) return 'Linux';
        if (preg_match('/Android/', $userAgent)) return 'Android';
        if (preg_match('/iOS/', $userAgent)) return 'iOS';
        return 'Unknown Platform';
    }

    /**
     * End the session.
     */
    public function endSession(): bool
    {
        return $this->update([
            'logged_out_at' => now(),
            'is_current' => false
        ]);
    }

    /**
     * Update last activity.
     */
    public function updateActivity(): bool
    {
        return $this->update(['last_activity' => now()]);
    }
}

/**
 * LoginHistory Model - app/Models/LoginHistory.php
 */
class LoginHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'login_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'failure_reason',
        'attempted_at',
        'country',
        'city',
        'device_info'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempted_at' => 'datetime',
        'successful' => 'boolean',
        'user_id' => 'integer',
        'device_info' => 'array'
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_text',
        'device_summary',
        'location_summary'
    ];

    /**
     * Get the user that owns the login history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include successful logins.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }

    /**
     * Scope a query to only include failed logins.
     */
    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope a query to filter by IP address.
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope a query for recent attempts.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('attempted_at', '>=', now()->subHours($hours));
    }

    /**
     * Get status text.
     */
    public function getStatusTextAttribute(): string
    {
        return $this->successful ? 'Success' : 'Failed';
    }

    /**
     * Get device summary.
     */
    public function getDeviceSummaryAttribute(): string
    {
        $deviceInfo = $this->device_info ?? [];
        $browser = $deviceInfo['browser'] ?? 'Unknown Browser';
        $platform = $deviceInfo['platform'] ?? 'Unknown Platform';
        return "{$browser} on {$platform}";
    }

    /**
     * Get location summary.
     */
    public function getLocationSummaryAttribute(): string
    {
        if ($this->city && $this->country) {
            return "{$this->city}, {$this->country}";
        }
        
        if ($this->country) {
            return $this->country;
        }
        
        return $this->ip_address;
    }

    /**
     * Check if this is a suspicious login.
     */
    public function isSuspicious(): bool
    {
        // Check for failed attempts from same IP
        $recentFailures = static::byIp($this->ip_address)
            ->failed()
            ->recent(1)
            ->count();

        return $recentFailures >= 3;
    }

    /**
     * Get failed attempts for IP in timeframe.
     */
    public static function getFailedAttemptsForIp(string $ip, int $hours = 1): int
    {
        return static::byIp($ip)
            ->failed()
            ->recent($hours)
            ->count();
    }

    /**
     * Get failed attempts for user in timeframe.
     */
    public static function getFailedAttemptsForUser(int $userId, int $hours = 1): int
    {
        return static::byUser($userId)
            ->failed()
            ->recent($hours)
            ->count();
    }

    /**
     * Clean up old login history.
     */
    public static function cleanupOld(int $days = 90): int
    {
        return static::where('attempted_at', '<', now()->subDays($days))->delete();
    }
}