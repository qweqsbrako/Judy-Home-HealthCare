<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'driver_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'date_of_birth',
        'avatar',
        'is_active',
        'average_rating',
        'total_trips',
        'completed_trips',
        'cancelled_trips',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        'suspended_by',
        'notes'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'average_rating' => 'decimal:2',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime'
    ];

    protected $appends = [
        'full_name',
        'age',
        'avatar_url',
        'completion_rate'
    ];

    // Boot method to generate driver ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($driver) {
            if (empty($driver->driver_id)) {
                $driver->driver_id = self::generateDriverId();
            }
        });
    }

    // Relationships
    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function vehicleAssignments()
    {
        return $this->hasMany(DriverVehicleAssignment::class);
    }

    public function activeVehicleAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class)->where('is_active', true);
    }

    public function currentVehicle()
    {
        return $this->hasOneThrough(
            Vehicle::class,
            DriverVehicleAssignment::class,
            'driver_id',
            'id',
            'id',
            'vehicle_id'
        )->where('driver_vehicle_assignments.is_active', true);
    }

    /**
     * Check if driver can accept new transport assignments
     */
    public function canAcceptNewTransport(): bool
    {
        // Driver must be active and not suspended
        if (!$this->is_active || $this->is_suspended) {
            return false;
        }
        
        // Driver should have a vehicle assigned - use the relationship method
        if (!$this->currentVehicle) {
            return false;
        }
        
        // Check if driver doesn't have any active/ongoing transports
        $activeTransports = $this->transportRequests()
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();
            
        if ($activeTransports > 0) {
            return false;
        }
        
        return true;
    }

/**
 * Relationship with transports
 */
public function transports()
{
    return $this->hasMany(Transport::class, 'driver_id');
}

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        return "https://ui-avatars.com/api/?name=" . urlencode($this->full_name) . "&color=667eea&background=f8f9fa&size=200&font-size=0.6";
    }

    public function getCompletionRateAttribute()
    {
        $total = $this->total_trips ?? 0;
        if ($total == 0) return 0;

        return round(($this->completed_trips / $total) * 100, 2);
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                    ->where('is_suspended', false);
    }

    public function scopeSuspended($query)
    {
        return $query->where('is_suspended', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('driver_id', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function scopeWithVehicle($query)
    {
        return $query->whereHas('activeVehicleAssignment');
    }

    public function scopeWithoutVehicle($query)
    {
        return $query->whereDoesntHave('activeVehicleAssignment');
    }

    // Methods
    public static function generateDriverId()
    {
        do {
            $id = 'DRV' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('driver_id', $id)->exists());

        return $id;
    }

    public function suspend($reason, $suspendedBy = null)
    {
        $this->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
            'suspended_by' => $suspendedBy
        ]);

        // Cancel any pending transport requests
        $this->transportRequests()
             ->whereIn('status', ['requested', 'assigned'])
             ->update([
                 'status' => 'cancelled',
                 'cancelled_at' => now(),
                 'cancellation_reason' => 'Driver suspended'
             ]);

        return $this;
    }

    public function reactivate()
    {
        $this->update([
            'is_suspended' => false,
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
            'suspended_by' => null
        ]);

        return $this;
    }

    public function assignVehicle($vehicleId, $assignedBy, $notes = null)
    {
        // Check if driver already has an active vehicle
        $existingAssignment = $this->activeVehicleAssignment;
        if ($existingAssignment) {
            throw new \Exception('Driver already has an active vehicle assignment');
        }

        // Check if vehicle is already assigned to another driver
        $vehicleAssignment = DriverVehicleAssignment::where('vehicle_id', $vehicleId)
                                                   ->where('is_active', true)
                                                   ->first();
        if ($vehicleAssignment) {
            throw new \Exception('Vehicle is already assigned to another driver');
        }

        return DriverVehicleAssignment::create([
            'driver_id' => $this->id,
            'vehicle_id' => $vehicleId,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
            'assignment_notes' => $notes,
            'is_active' => true,
            'status' => 'active'
        ]);
    }

    public function unassignVehicle($unassignedBy, $reason = null)
    {
        $assignment = $this->activeVehicleAssignment;
        if (!$assignment) {
            throw new \Exception('Driver has no active vehicle assignment');
        }

        $assignment->update([
            'is_active' => false,
            'unassigned_at' => now(),
            'unassigned_by' => $unassignedBy,
            'unassignment_reason' => $reason,
            'status' => 'inactive'
        ]);

        return $assignment;
    }

    public function updateRating()
    {
        $averageRating = $this->transportRequests()
                             ->whereNotNull('rating')
                             ->avg('rating');

        $this->update(['average_rating' => $averageRating ? round($averageRating, 2) : null]);

        return $this;
    }


    public function isAvailableForTransport()
    {
        return $this->is_active 
            && !$this->is_suspended 
            && $this->activeVehicleAssignment
            && !$this->hasActiveTransport();
    }

    public function hasActiveTransport()
    {
        return $this->transportRequests()
                   ->whereIn('status', ['assigned', 'in_progress'])
                   ->exists();
    }

    public function getPerformanceMetrics()
    {
        return [
            'total_trips' => $this->total_trips,
            'completed_trips' => $this->completed_trips,
            'cancelled_trips' => $this->cancelled_trips,
            'completion_rate' => $this->completion_rate,
            'average_rating' => $this->average_rating,
            'current_vehicle' => $this->currentVehicle ? $this->currentVehicle->registration_number : null
        ];
    }

    public function transportRequests()
    {
        return $this->hasMany(TransportRequest::class, 'driver_id');
    }

    /**
     * Get only active transport requests for this driver
     */
    public function activeTransportRequests()
    {
        return $this->hasMany(TransportRequest::class, 'driver_id')
            ->whereIn('status', ['assigned', 'in_progress']);
    }


    /**
     * Scope to get only drivers available for transport
     * This checks the transport_requests table for active trips
     */
    public function scopeAvailableForTransport($query)
    {
        return $query->whereDoesntHave('transportRequests', function($q) {
            $q->whereIn('status', ['assigned', 'in_progress']);
        });
    }

}