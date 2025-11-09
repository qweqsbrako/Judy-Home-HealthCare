<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_type',
        'notification_type',
        'title',
        'body',
        'data',
        'notifiable_type',
        'notifiable_id',
        'sent_via_push',
        'sent_via_email',
        'sent_via_sms',
        'status',
        'failure_reason',
        'fcm_message_id',
        'fcm_response',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'priority',
        'scheduled_for',
    ];

    protected $casts = [
        'data' => 'array',
        'fcm_response' => 'array',
        'sent_via_push' => 'boolean',
        'sent_via_email' => 'boolean',
        'sent_via_sms' => 'boolean',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'scheduled_for' => 'datetime',
    ];

    protected $appends = [
        'is_read',
        'time_ago',
    ];

    /**
     * Notification types constants
     */
    public const TYPE_APPOINTMENT_REMINDER = 'appointment_reminder';
    public const TYPE_MEDICATION_REMINDER = 'medication_reminder';
    public const TYPE_VITALS_REMINDER = 'vitals_reminder';
    public const TYPE_CARE_PLAN_UPDATE = 'care_plan_update';
    public const TYPE_PAYMENT_REMINDER = 'payment_reminder';
    public const TYPE_NURSE_ASSIGNED = 'nurse_assigned';
    public const TYPE_ASSESSMENT_SCHEDULED = 'assessment_scheduled';
    public const TYPE_CARE_STARTED = 'care_started';
    public const TYPE_CARE_COMPLETED = 'care_completed';
    public const TYPE_CARE_REQUEST_CREATED = 'care_request_created';
    public const TYPE_PAYMENT_RECEIVED = 'payment_received';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_READ = 'read';
    
    /**
     * Priority constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accessors
     */
    public function getIsReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

    public function getTimeAgoAttribute(): string
    {
        if (!$this->sent_at) {
            return 'Not sent';
        }
        return $this->sent_at->diffForHumans();
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_for')
            ->where('scheduled_for', '>', now())
            ->where('status', 'pending');
    }

    public function scopeDueToSend($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_for')
                  ->orWhere('scheduled_for', '<=', now());
            });
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Helper Methods
     */
    public function markAsSent(array $data = []): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'fcm_message_id' => $data['fcm_message_id'] ?? null,
            'fcm_response' => $data['fcm_response'] ?? null,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'read_at' => now(),
            'status' => 'read',
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);
    }

    public function retry(): void
    {
        $this->update([
            'status' => 'pending',
            'failure_reason' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * Get notification icon based on type
     */
    public function getIcon(): string
    {
        return match($this->notification_type) {
            self::TYPE_APPOINTMENT_REMINDER => '📅',
            self::TYPE_MEDICATION_REMINDER => '💊',
            self::TYPE_VITALS_REMINDER => '❤️',
            self::TYPE_CARE_PLAN_UPDATE => '📋',
            self::TYPE_PAYMENT_REMINDER => '💳',
            self::TYPE_NURSE_ASSIGNED => '👩‍⚕️',
            self::TYPE_ASSESSMENT_SCHEDULED => '🏥',
            self::TYPE_CARE_STARTED => '✅',
            self::TYPE_CARE_COMPLETED => '🎉',
            default => '🔔',
        };
    }

    /**
     * Get notification color based on priority
     */
    public function getColorCode(): string
    {
        return match($this->priority) {
            'urgent' => '#FF0000',
            'high' => '#FF6B00',
            'normal' => '#007AFF',
            'low' => '#8E8E93',
            default => '#007AFF',
        };
    }

    /**
     * Static helper to create notification log
     */
    public static function createLog(array $data): self
    {
        return static::create([
            'user_id' => $data['user_id'],
            'user_type' => $data['user_type'] ?? 'patient',
            'notification_type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => $data['data'] ?? null,
            'notifiable_type' => $data['notifiable_type'] ?? null,
            'notifiable_id' => $data['notifiable_id'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'status' => 'pending',
        ]);
    }
}