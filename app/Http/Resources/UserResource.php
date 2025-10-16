<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'role_display' => ucfirst($this->role),
            
            // Personal Information
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->age,
            'ghana_card_number' => $this->ghana_card_number,
            'avatar_url' => $this->avatar_url,
            
            // Professional Information (for healthcare professionals)
            'license_number' => $this->when(
                in_array($this->role, ['nurse', 'doctor']),
                $this->license_number
            ),
            'specialization' => $this->when(
                in_array($this->role, ['nurse', 'doctor']),
                $this->specialization
            ),
            'years_experience' => $this->when(
                in_array($this->role, ['nurse', 'doctor']),
                $this->years_experience
            ),
            'is_healthcare_professional' => $this->is_healthcare_professional,
            
            // Emergency Contact (for patients)
            'emergency_contact_name' => $this->when(
                $this->role === 'patient',
                $this->emergency_contact_name
            ),
            'emergency_contact_phone' => $this->when(
                $this->role === 'patient',
                $this->emergency_contact_phone
            ),
            
            // Medical Information (for patients)
            'medical_conditions' => $this->when(
                $this->role === 'patient',
                $this->medical_conditions
            ),
            'allergies' => $this->when(
                $this->role === 'patient',
                $this->allergies
            ),
            'current_medications' => $this->when(
                $this->role === 'patient',
                $this->current_medications
            ),
            
            // Account Status
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'verification_status' => $this->verification_status,
            'verification_status_text' => $this->verification_status_text,
            'verification_notes' => $this->verification_notes,
            
            // Verification Details
            'verified_by' => $this->when(
                $this->verified_by && $this->relationLoaded('verifier'),
                [
                    'id' => $this->verifier?->id,
                    'name' => $this->verifier?->full_name,
                    'email' => $this->verifier?->email
                ]
            ),
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            
            // Security Information
            'two_factor_enabled' => $this->two_factor_enabled,
            'two_factor_method' => $this->two_factor_method,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'last_login_ip' => $this->when(
                $request->user()?->isAdmin(),
                $this->last_login_ip
            ),
            'password_changed_at' => $this->password_changed_at?->format('Y-m-d H:i:s'),
            'force_password_change' => $this->force_password_change,
            
            // Role Information (if using role system)
            'role_model' => $this->when(
                $this->relationLoaded('roleModel') && $this->roleModel,
                [
                    'id' => $this->roleModel?->id,
                    'name' => $this->roleModel?->name,
                    'display_name' => $this->roleModel?->display_name,
                    'description' => $this->roleModel?->description,
                ]
            ),
            
            // System Information
            'preferences' => $this->preferences,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'registered_ip' => $this->when(
                $request->user()?->isAdmin(),
                $this->registered_ip
            ),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when(
                $this->deleted_at,
                fn () => $this->deleted_at->format('Y-m-d H:i:s')
            ),
            
            // Computed Properties
            'time_since_last_login' => $this->getTimeSinceLastLogin(),
            'account_age_days' => $this->created_at->diffInDays(now()),
            'is_new_user' => $this->created_at->isAfter(now()->subDays(7)),
            'needs_verification' => $this->verification_status === 'pending',
            'can_login' => $this->is_active && $this->is_verified,
        ];
    }
    
    /**
     * Get time since last login in human readable format
     */
    private function getTimeSinceLastLogin(): ?string
    {
        if (!$this->last_login_at) {
            return 'Never';
        }
        
        $diffInDays = $this->last_login_at->diffInDays(now());
        
        if ($diffInDays === 0) {
            $diffInHours = $this->last_login_at->diffInHours(now());
            if ($diffInHours === 0) {
                $diffInMinutes = $this->last_login_at->diffInMinutes(now());
                return $diffInMinutes . ' minutes ago';
            }
            return $diffInHours . ' hours ago';
        } elseif ($diffInDays === 1) {
            return '1 day ago';
        } elseif ($diffInDays < 7) {
            return $diffInDays . ' days ago';
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } else {
            $months = floor($diffInDays / 30);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        }
    }
}