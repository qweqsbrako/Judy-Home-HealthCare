<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverVehicleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'assigned_at',
        'unassigned_at',
        'is_active',
        'assigned_by',
        'unassigned_by',
        'assignment_notes',
        'unassignment_reason',
        'status',
        'effective_from',
        'effective_until'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime'
    ];

    protected $appends = [
        'duration',
        'status_label'
    ];

    // Relationships
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function unassignedBy()
    {
        return $this->belongsTo(User::class, 'unassigned_by');
    }

    // Accessors
    public function getDurationAttribute()
    {
        $start = $this->assigned_at;
        $end = $this->unassigned_at ?? now();
        
        return $start->diffForHumans($end, true);
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeByVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('assigned_at', [$startDate, $endDate]);
    }

    public function scopeEffectiveNow($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>', $now);
        });
    }

    public function scopeTemporary($query)
    {
        return $query->where('status', 'temporary')
                    ->whereNotNull('effective_until');
    }

    // Methods
    public function activate()
    {
        $this->update([
            'is_active' => true,
            'status' => 'active'
        ]);

        return $this;
    }

    public function deactivate($reason = null, $unassignedBy = null)
    {
        $this->update([
            'is_active' => false,
            'status' => 'inactive',
            'unassigned_at' => now(),
            'unassignment_reason' => $reason,
            'unassigned_by' => $unassignedBy
        ]);

        return $this;
    }

    public function isEffectiveNow()
    {
        $now = now();
        
        // Check if assignment has started
        $hasStarted = !$this->effective_from || $this->effective_from <= $now;
        
        // Check if assignment hasn't expired
        $hasntExpired = !$this->effective_until || $this->effective_until > $now;
        
        return $this->is_active && $hasStarted && $hasntExpired;
    }

    public function isTemporary()
    {
        return $this->status === 'temporary' && $this->effective_until;
    }

    public function getDaysUntilExpiry()
    {
        if (!$this->effective_until) {
            return null; // Permanent assignment
        }
        
        return now()->diffInDays($this->effective_until, false);
    }

    public function isExpiring($days = 7)
    {
        $daysUntilExpiry = $this->getDaysUntilExpiry();
        
        return $daysUntilExpiry !== null && $daysUntilExpiry <= $days && $daysUntilExpiry > 0;
    }

    public function isExpired()
    {
        return $this->effective_until && $this->effective_until <= now();
    }

    public function extend($newEndDate, $notes = null)
    {
        if (!$this->isTemporary()) {
            throw new \Exception('Cannot extend a permanent assignment');
        }

        $this->update([
            'effective_until' => $newEndDate,
            'assignment_notes' => $this->assignment_notes . 
                                   ($notes ? "\nExtended: " . $notes : "\nExtended until " . $newEndDate)
        ]);

        return $this;
    }

    public function makePermanent($notes = null)
    {
        $this->update([
            'status' => 'active',
            'effective_until' => null,
            'assignment_notes' => $this->assignment_notes . 
                                   ($notes ? "\nMade permanent: " . $notes : "\nMade permanent")
        ]);

        return $this;
    }

    public function getAssignmentSummary()
    {
        return [
            'driver' => $this->driver->full_name,
            'vehicle' => $this->vehicle->vehicle_info,
            'assigned_at' => $this->assigned_at,
            'assigned_by' => $this->assignedBy->name ?? 'System',
            'status' => $this->status_label,
            'is_active' => $this->is_active,
            'is_temporary' => $this->isTemporary(),
            'is_expired' => $this->isExpired(),
            'duration' => $this->duration,
            'days_until_expiry' => $this->getDaysUntilExpiry()
        ];
    }
}