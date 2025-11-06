<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'role',
        'date_of_birth',
        'gender',
        'address',
        'ghana_card_number',
        'license_number',
        'specialization',
        'years_experience',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_conditions',
        'allergies',
        'current_medications',
        'avatar',
        'is_active',
        'is_verified',
        'verification_status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'two_factor_enabled',
        'two_factor_method',
        'two_factor_enabled_at',
        'two_factor_disabled_at',
        'two_factor_verified_at',
        'security_question',
        'security_answer_hash',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'force_password_change',
        'registered_ip',
        'preferences',
        'timezone',
        'locale',
        'password_reset_token', 
        'password_reset_expires',
        'fcm_token',
        'fcm_token_updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'security_answer_hash',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'verified_at' => 'datetime',
        'two_factor_enabled_at' => 'datetime',
        'two_factor_disabled_at' => 'datetime',
        'two_factor_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'force_password_change' => 'boolean',
        'medical_conditions' => 'array',
        'allergies' => 'array',
        'current_medications' => 'array',
        'preferences' => 'array',
        'years_experience' => 'integer',
        'fcm_token_updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
        'avatar_url',
        'age',
        'is_healthcare_professional',
        'verification_status_text'
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set default verification status based on role
            if (!$user->verification_status) {
                $user->verification_status = $user->role === 'patient' ? 'verified' : 'pending';
            }
            
            // Set timezone if not provided
            if (!$user->timezone) {
                $user->timezone = config('app.timezone', 'UTC');
            }
            
            // Set locale if not provided
            if (!$user->locale) {
                $user->locale = config('app.locale', 'en');
            }
        });
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's avatar URL.
     *
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::disk('public')->url($this->avatar);
        }

        // Generate initials-based avatar or use default
        $initials = strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        return "https://ui-avatars.com/api/?name={$initials}&color=667eea&background=f8f9fa&size=200&font-size=0.6";
    }

    /**
     * Get the user's age.
     *
     * @return int|null
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Check if user is a healthcare professional.
     *
     * @return bool
     */
    public function getIsHealthcareProfessionalAttribute(): bool
    {
        return in_array($this->role, ['nurse', 'doctor']);
    }

    /**
     * Get verification status text.
     *
     * @return string
     */
    public function getVerificationStatusTextAttribute(): string
    {
        return match($this->verification_status) {
            'pending' => 'Pending Verification',
            'verified' => 'Verified',
            'rejected' => 'Verification Rejected',
            'suspended' => 'Account Suspended',
            default => 'Unknown Status'
        };
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to filter by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to filter by verification status.
     */
    public function scopeByVerificationStatus($query, string $status)
    {
        return $query->where('verification_status', $status);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user can access admin features.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user account is pending verification.
     */
    public function isPendingVerification(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if user account is rejected.
     */
    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Relationships
     */

    /**
     * Get the user's password reset tokens.
     */
    public function passwordResets(): HasMany
    {
        return $this->hasMany(PasswordReset::class, 'email', 'email');
    }

    /**
     * Get the user's two-factor codes.
     */
    public function twoFactorCodes(): HasMany
    {
        return $this->hasMany(TwoFactorCode::class);
    }

    /**
     * Get the user's login history.
     */
    public function loginHistory(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    /**
     * Get the user's active sessions.
     */
    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Get the user who verified this user.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get users verified by this user.
     */
    public function verifiedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'verified_by');
    }

    /**
     * Helper Methods
     */

    /**
     * Mark user as verified.
     */
    public function markAsVerified(User $verifier = null, string $notes = null): bool
    {
        return $this->update([
            'is_verified' => true,
            'verification_status' => 'verified',
            'verified_by' => $verifier?->id,
            'verified_at' => now(),
            'verification_notes' => $notes
        ]);
    }

    /**
     * Reject user verification.
     */
    public function rejectVerification(User $verifier = null, string $notes = null): bool
    {
        return $this->update([
            'is_verified' => false,
            'verification_status' => 'rejected',
            'verified_by' => $verifier?->id,
            'verified_at' => now(),
            'verification_notes' => $notes
        ]);
    }

    /**
     * Suspend user account.
     */
    public function suspend(User $admin = null, string $reason = null): bool
    {
        return $this->update([
            'is_active' => false,
            'verification_status' => 'suspended',
            'verified_by' => $admin?->id,
            'verification_notes' => $reason
        ]);
    }

    /**
     * Activate user account.
     */
    public function activate(User $admin = null): bool
    {
        return $this->update([
            'is_active' => true,
            'verification_status' => 'verified',
            // 'verification_status' => $this->is_verified ? 'verified' : 'pending',
            'verified_by' => $admin?->id
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function enableTwoFactor(string $method = 'email'): bool
    {
        return $this->update([
            'two_factor_enabled' => true,
            'two_factor_method' => $method,
            'two_factor_enabled_at' => now()
        ]);
    }

    /**
     * Disable two-factor authentication.
     */
    public function disableTwoFactor(): bool
    {
        return $this->update([
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'two_factor_disabled_at' => now()
        ]);
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(string $ip = null): bool
    {
        return $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip
        ]);
    }

    /**
     * Check if password needs to be changed.
     */
    public function needsPasswordChange(): bool
    {
        return $this->force_password_change === true;
    }

    /**
     * Force user to change password on next login.
     */
    public function forcePasswordChange(): bool
    {
        return $this->update(['force_password_change' => true]);
    }

    /**
     * Get user's medical information (for patients).
     */
    public function getMedicalInfo(): array
    {
        return [
            'conditions' => $this->medical_conditions ?? [],
            'allergies' => $this->allergies ?? [],
            'medications' => $this->current_medications ?? [],
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone
            ]
        ];
    }

    /**
     * Get user's professional information (for healthcare professionals).
     */
    public function getProfessionalInfo(): array
    {
        return [
            'license_number' => $this->license_number,
            'specialization' => $this->specialization,
            'years_experience' => $this->years_experience,
            'verification_status' => $this->verification_status,
            'verified_at' => $this->verified_at
        ];
    }

    /**
     * Get user's security information.
     */
    public function getSecurityInfo(): array
    {
        return [
            'two_factor_enabled' => $this->two_factor_enabled,
            'two_factor_method' => $this->two_factor_method,
            'last_login_at' => $this->last_login_at,
            'last_login_ip' => $this->last_login_ip,
            'email_verified_at' => $this->email_verified_at,
            'password_changed_at' => $this->password_changed_at
        ];
    }

        /**
     * Get the user's role
     */
    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roleModel && $this->roleModel->hasPermission($permission);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roleModel && $this->roleModel->hasAnyPermission($permissions);
    }

    /**
     * Get user's permissions through role
     */
    public function getPermissions()
    {
        return $this->roleModel ? $this->roleModel->permissions : collect();
    }

    /**
     * Get user's permissions grouped by category
     */
    public function getGroupedPermissions(): array
    {
        return $this->roleModel ? $this->roleModel->getGroupedPermissions() : [];
    }

    /**
     * Assign role to user
     */
    public function assignRole(Role $role): void
    {
        $this->role_id = $role->id;
        $this->role = $role->name; // Keep the string role for backwards compatibility
        $this->save();
    }

    /**
     * Remove role from user
     */
    public function removeRole(): void
    {
        $this->role_id = null;
        $this->role = 'patient'; // Default role
        $this->save();
    }

    public function carePlansAsPatient()
    {
        return $this->hasMany(CarePlan::class, 'patient_id');
    }

    public function carePlansAsDoctor()
    {
        return $this->hasMany(CarePlan::class, 'doctor_id');
    }

    public function primaryAssignments()
    {
        return $this->hasMany(CareAssignment::class, 'primary_nurse_id');
    }

    public function secondaryAssignments()
    {
        return $this->hasMany(CareAssignment::class, 'secondary_nurse_id');
    }

    public function nurseSchedules()
    {
        return $this->hasMany(Schedule::class, 'nurse_id');
    }

    public function patientSchedules()
    {
        return $this->hasMany(Schedule::class, 'patient_id');
    }

    public function feedback()
    {
        return $this->hasMany(PatientFeedback::class, 'nurse_id');
    }

    public function givenFeedback()
    {
        return $this->hasMany(PatientFeedback::class, 'patient_id');
    }

    /**
     * Incident reports made by this user
     */
    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'reported_by');
    }

    /**
     * Incident reports involving this nurse
     */
    public function nurseIncidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'reported_by');
    }

    /**
     * Incident reports involving this patient
     */
    public function patientIncidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'patient_id');
    }

    public function progressNotes()
    {
        return $this->hasMany(ProgressNote::class, 'patient_id')
            ->orderBy('visit_date', 'desc');
    }

    public function latestProgressNote()
    {
        return $this->hasOne(ProgressNote::class, 'patient_id')->latest('visit_date');
    }

    /**
     * Get feedback received (for nurses)
     */
    public function feedbackReceived()
    {
        return $this->hasMany(PatientFeedback::class, 'nurse_id');
    }

    /**
     * Get feedback given (for patients)
     */
    public function feedbackGiven()
    {
        return $this->hasMany(PatientFeedback::class, 'patient_id');
    }

    // Add relationship
    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    // Helper to update FCM token
    public function updateFcmToken(string $token): void
    {
        $this->update([
            'fcm_token' => $token,
            'fcm_token_updated_at' => now(),
        ]);
    }
}