<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'vehicle_type',
        'registration_number',
        'vehicle_color',
        'make',
        'model',
        'year',
        'vin_number',
        'is_active',
        'is_available',
        'status',
        'last_service_date',
        'next_service_date',
        'mileage',
        'insurance_policy',
        'insurance_expiry',
        'registration_expiry',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'insurance_expiry' => 'date',
        'registration_expiry' => 'date',
        'mileage' => 'decimal:2'
    ];

    protected $appends = [
        'vehicle_info',
        'status_label',
        'insurance_status',
        'registration_status'
    ];

    // Boot method to generate vehicle ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vehicle) {
            if (empty($vehicle->vehicle_id)) {
                $vehicle->vehicle_id = self::generateVehicleId();
            }
        });
    }

    // Relationships
    public function driverAssignments()
    {
        return $this->hasMany(DriverVehicleAssignment::class);
    }

    public function activeDriverAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class)->where('is_active', true);
    }

    public function currentDriver()
    {
        return $this->hasOneThrough(
            Driver::class,
            DriverVehicleAssignment::class,
            'vehicle_id',
            'id',
            'id',
            'driver_id'
        )->where('driver_vehicle_assignments.is_active', true);
    }

    public function transportRequests()
    {
        return $this->hasMany(TransportRequest::class);
    }

    // Accessors
    public function getVehicleInfoAttribute()
    {
        $info = $this->registration_number;
        if ($this->make && $this->model) {
            $info .= " ({$this->make} {$this->model})";
        }
        return $info;
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getInsuranceStatusAttribute()
    {
        if (!$this->insurance_expiry) return 'unknown';
        
        $daysUntilExpiry = now()->diffInDays($this->insurance_expiry, false);
        
        if ($daysUntilExpiry < 0) return 'expired';
        if ($daysUntilExpiry <= 30) return 'expiring_soon';
        return 'valid';
    }

    public function getRegistrationStatusAttribute()
    {
        if (!$this->registration_expiry) return 'unknown';
        
        $daysUntilExpiry = now()->diffInDays($this->registration_expiry, false);
        
        if ($daysUntilExpiry < 0) return 'expired';
        if ($daysUntilExpiry <= 30) return 'expiring_soon';
        return 'valid';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                    ->where('is_available', true)
                    ->where('status', 'available');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vehicle_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssigned($query)
    {
        return $query->whereHas('activeDriverAssignment');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereDoesntHave('activeDriverAssignment');
    }

    public function scopeInsuranceExpiring($query, $days = 30)
    {
        return $query->where('insurance_expiry', '<=', now()->addDays($days))
                    ->where('insurance_expiry', '>=', now());
    }

    public function scopeInsuranceExpired($query)
    {
        return $query->where('insurance_expiry', '<', now());
    }

    public function scopeRegistrationExpiring($query, $days = 30)
    {
        return $query->where('registration_expiry', '<=', now()->addDays($days))
                    ->where('registration_expiry', '>=', now());
    }

    public function scopeRegistrationExpired($query)
    {
        return $query->where('registration_expiry', '<', now());
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('registration_number', 'like', "%{$search}%")
              ->orWhere('vehicle_id', 'like', "%{$search}%")
              ->orWhere('make', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%")
              ->orWhere('vin_number', 'like', "%{$search}%");
        });
    }

    // Methods
    public static function generateVehicleId()
    {
        do {
            $id = 'VEH' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('vehicle_id', $id)->exists());

        return $id;
    }

    public function setAvailable()
    {
        $this->update([
            'status' => 'available',
            'is_available' => true
        ]);

        return $this;
    }

    public function setInUse()
    {
        $this->update([
            'status' => 'in_use',
            'is_available' => false
        ]);

        return $this;
    }

    public function setMaintenance()
    {
        $this->update([
            'status' => 'maintenance',
            'is_available' => false
        ]);

        return $this;
    }

    public function setOutOfService()
    {
        $this->update([
            'status' => 'out_of_service',
            'is_available' => false
        ]);

        return $this;
    }

    public function assignDriver($driverId, $assignedBy, $notes = null)
    {
        // Check if vehicle already has an active driver
        $existingAssignment = $this->activeDriverAssignment;
        if ($existingAssignment) {
            throw new \Exception('Vehicle already has an active driver assignment');
        }

        // Check if driver is already assigned to another vehicle
        $driverAssignment = DriverVehicleAssignment::where('driver_id', $driverId)
                                                   ->where('is_active', true)
                                                   ->first();
        if ($driverAssignment) {
            throw new \Exception('Driver is already assigned to another vehicle');
        }

        return DriverVehicleAssignment::create([
            'driver_id' => $driverId,
            'vehicle_id' => $this->id,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
            'assignment_notes' => $notes,
            'is_active' => true,
            'status' => 'active'
        ]);
    }

    public function unassignDriver($unassignedBy, $reason = null)
    {
        $assignment = $this->activeDriverAssignment;
        if (!$assignment) {
            throw new \Exception('Vehicle has no active driver assignment');
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

    public function isInsuranceValid()
    {
        return $this->insurance_expiry && $this->insurance_expiry > now();
    }

    public function isRegistrationValid()
    {
        return $this->registration_expiry && $this->registration_expiry > now();
    }

    public function isInsuranceExpiringSoon($days = 30)
    {
        return $this->insurance_expiry 
            && $this->insurance_expiry <= now()->addDays($days) 
            && $this->insurance_expiry > now();
    }

    public function isRegistrationExpiringSoon($days = 30)
    {
        return $this->registration_expiry 
            && $this->registration_expiry <= now()->addDays($days) 
            && $this->registration_expiry > now();
    }

    public function getCompletedTripsCount()
    {
        return $this->transportRequests()
                   ->where('status', 'completed')
                   ->count();
    }

    public function getTotalTripsCount()
    {
        return $this->transportRequests()->count();
    }

    public function getUsageMetrics()
    {
        return [
            'total_trips' => $this->getTotalTripsCount(),
            'completed_trips' => $this->getCompletedTripsCount(),
            'current_driver' => $this->currentDriver ? $this->currentDriver->full_name : null,
            'current_mileage' => $this->mileage,
            'insurance_status' => $this->insurance_status,
            'registration_status' => $this->registration_status
        ];
    }
}