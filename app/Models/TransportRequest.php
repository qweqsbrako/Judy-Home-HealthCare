<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'requested_by_id',
        'transport_type',
        'priority',
        'scheduled_time',
        'pickup_location',
        'pickup_address',
        'destination_location',
        'destination_address',
        'reason',
        'special_requirements',
        'contact_person',
        'driver_id',
        'status',
        'actual_pickup_time',
        'actual_arrival_time',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'estimated_cost',
        'actual_cost',
        'distance_km',
        'rating',
        'feedback',
        'metadata',
        'notes'
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'actual_pickup_time' => 'datetime',
        'actual_arrival_time' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'metadata' => 'array',
        'rating' => 'integer'
    ];

    protected $appends = [
        'patient_name',
        'requested_by_name',
        'driver_name',
        'status_label',
        'priority_label',
        'type_label'
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    // Accessors
    public function getPatientNameAttribute()
    {
        return $this->patient ? $this->patient->first_name . ' ' . $this->patient->last_name : 'Unknown';
    }

    public function getRequestedByNameAttribute()
    {
        return $this->requestedBy ? $this->requestedBy->first_name . ' ' . $this->requestedBy->last_name : 'Unknown';
    }

    public function getDriverNameAttribute()
    {
        return $this->driver ? $this->driver->first_name . ' ' . $this->driver->last_name : null;
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getPriorityLabelAttribute()
    {
        return ucfirst($this->priority);
    }

    public function getTypeLabelAttribute()
    {
        return ucfirst($this->transport_type);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transport_type', $type);
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['requested', 'assigned']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['requested', 'assigned', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeScheduledToday($query)
    {
        return $query->whereDate('scheduled_time', today());
    }

    public function scopeScheduledBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_time', [$startDate, $endDate]);
    }

    public function scopeEmergency($query)
    {
        return $query->where('priority', 'emergency');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('patient', function ($patientQuery) use ($search) {
                $patientQuery->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->orWhereHas('driver', function ($driverQuery) use ($search) {
                $driverQuery->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->orWhere('pickup_location', 'like', "%{$search}%")
            ->orWhere('destination_location', 'like', "%{$search}%")
            ->orWhere('reason', 'like', "%{$search}%");
        });
    }

    // Methods
    public function assignDriver($driverId)
    {
        $this->update([
            'driver_id' => $driverId,
            'status' => 'assigned'
        ]);

        // Update driver status to busy
        $driver = Driver::find($driverId);
        if ($driver) {
            $driver->update(['status' => 'busy']);
        }

        return $this;
    }

    public function startTransport()
    {
        $this->update([
            'status' => 'in_progress',
            'actual_pickup_time' => now()
        ]);

        return $this;
    }

    public function completeTransport($actualCost = null, $feedback = null, $rating = null)
    {
        $this->update([
            'status' => 'completed',
            'actual_arrival_time' => now(),
            'completed_at' => now(),
            'actual_cost' => $actualCost,
            'feedback' => $feedback,
            'rating' => $rating
        ]);

        // Update driver status to available and increment trip count
        if ($this->driver) {
            $this->driver->update([
                'status' => 'available',
                'total_trips' => $this->driver->total_trips + 1,
                'completed_trips' => $this->driver->completed_trips + 1
            ]);

            // Update driver's average rating
            $this->driver->updateRating();
        }

        return $this;
    }

    public function cancelTransport($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);

        // Make driver available again
        if ($this->driver) {
            $this->driver->update([
                'status' => 'available',
                'cancelled_trips' => $this->driver->cancelled_trips + 1
            ]);
        }

        return $this;
    }

    public function calculateEstimatedCost()
    {
        // Basic cost calculation logic
        $baseCost = $this->transport_type === 'ambulance' ? 50 : 20;
        $priorityMultiplier = match($this->priority) {
            'emergency' => 2.0,
            'urgent' => 1.5,
            'routine' => 1.0
        };

        // Add distance-based cost if available
        $distanceCost = $this->distance_km ? $this->distance_km * 2 : 0;

        return ($baseCost + $distanceCost) * $priorityMultiplier;
    }

    public function isOverdue()
    {
        return $this->scheduled_time < now() && in_array($this->status, ['requested', 'assigned']);
    }

    public function canBeModified()
    {
        return in_array($this->status, ['requested', 'assigned']);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['requested', 'assigned']);
    }

    public function getDurationAttribute()
    {
        if ($this->actual_pickup_time && $this->actual_arrival_time) {
            return $this->actual_pickup_time->diffInMinutes($this->actual_arrival_time);
        }
        return null;
    }
}