<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\DriverVehicleAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TransportRequest;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles
     */
    public function index(Request $request)
    {
        $query = Vehicle::query()->with(['currentDriver', 'activeDriverAssignment']);

        // Apply filters
        if ($request->filled('vehicle_type')) {
            $query->byType($request->vehicle_type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('active')) {
            if ($request->active === 'active') {
                $query->active();
            } elseif ($request->active === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('assigned')) {
            if ($request->assigned === 'true') {
                $query->assigned();
            } elseif ($request->assigned === 'false') {
                $query->unassigned();
            }
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Check for expiring documents
        if ($request->filled('insurance_expiring')) {
            $query->insuranceExpiring($request->get('insurance_expiring', 30));
        }

        if ($request->filled('registration_expiring')) {
            $query->registrationExpiring($request->get('registration_expiring', 30));
        }

        $query->orderBy('created_at', 'desc');

        $vehicles = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $vehicles->items(),
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total()
            ]
        ]);
    }

    /**
     * Store a new vehicle
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|in:ambulance,regular',
            'registration_number' => 'required|string|max:20|unique:vehicles',
            'vehicle_color' => 'required|string|max:255',
            'make' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 2),
            'vin_number' => 'nullable|string|max:17|unique:vehicles',
            'mileage' => 'nullable|numeric|min:0',
            'insurance_policy' => 'nullable|string|max:255',
            'insurance_expiry' => 'nullable|date|after:today',
            'registration_expiry' => 'nullable|date|after:today',
            'last_service_date' => 'nullable|date|before_or_equal:today',
            'next_service_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $vehicle = Vehicle::create([
                'vehicle_type' => $request->vehicle_type,
                'registration_number' => $request->registration_number,
                'vehicle_color' => $request->vehicle_color,
                'make' => $request->make,
                'model' => $request->model,
                'year' => $request->year,
                'vin_number' => $request->vin_number,
                'mileage' => $request->mileage,
                'insurance_policy' => $request->insurance_policy,
                'insurance_expiry' => $request->insurance_expiry,
                'registration_expiry' => $request->registration_expiry,
                'last_service_date' => $request->last_service_date,
                'next_service_date' => $request->next_service_date,
                'notes' => $request->notes,
                'is_active' => true,
                'status' => 'available'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle added successfully!',
                'data' => $vehicle->load(['currentDriver', 'activeDriverAssignment'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add vehicle.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified vehicle
     */
    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['currentDriver', 'activeDriverAssignment']);
        
        // Add usage metrics
        $vehicle->usage_metrics = $vehicle->getUsageMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $vehicle
        ]);
    }

    /**
     * Update the specified vehicle
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'vehicle_type' => 'required|in:ambulance,regular',
            'registration_number' => 'required|string|max:20|unique:vehicles,registration_number,' . $vehicle->id,
            'vehicle_color' => 'required|string|max:255',
            'make' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 2),
            'vin_number' => 'nullable|string|max:17|unique:vehicles,vin_number,' . $vehicle->id,
            'mileage' => 'nullable|numeric|min:0',
            'insurance_policy' => 'nullable|string|max:255',
            'insurance_expiry' => 'nullable|date|after:today',
            'registration_expiry' => 'nullable|date|after:today',
            'last_service_date' => 'nullable|date|before_or_equal:today',
            'next_service_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string'
        ]);

        $vehicle->update($request->only([
            'vehicle_type',
            'registration_number',
            'vehicle_color',
            'make',
            'model',
            'year',
            'vin_number',
            'mileage',
            'insurance_policy',
            'insurance_expiry',
            'registration_expiry',
            'last_service_date',
            'next_service_date',
            'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully!',
            'data' => $vehicle->load(['currentDriver', 'activeDriverAssignment'])
        ]);
    }

    /**
     * Update vehicle status
     */
    public function updateStatus(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'status' => 'required|in:available,in_use,maintenance,out_of_service'
        ]);

        // Prevent changing status if vehicle has active transport
        if ($vehicle->transportRequests()->whereIn('status', ['assigned', 'in_progress'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change status while vehicle has an active transport.'
            ], 422);
        }

        switch ($request->status) {
            case 'available':
                $vehicle->setAvailable();
                break;
            case 'in_use':
                $vehicle->setInUse();
                break;
            case 'maintenance':
                $vehicle->setMaintenance();
                break;
            case 'out_of_service':
                $vehicle->setOutOfService();
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Vehicle status updated successfully!',
            'data' => $vehicle
        ]);
    }

    /**
     * Get available vehicles (not assigned to any driver)
     */
    public function available(Request $request)
    {
        $query = Vehicle::available()->unassigned();

        if ($request->filled('vehicle_type')) {
            $query->byType($request->vehicle_type);
        }

        $vehicles = $query->orderBy('registration_number')->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }

    /**
     * Assign a driver to a vehicle
     */
    public function assignDriver(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'notes' => 'nullable|string'
        ]);

        try {
            $assignment = $vehicle->assignDriver(
                $request->driver_id,
                Auth::id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Driver assigned to vehicle successfully!',
                'data' => $assignment->load(['driver', 'vehicle'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to assign driver. Please try again"
            ], 422);
        }
    }

    /**
     * Unassign driver from vehicle
     */
    public function unassignDriver(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'reason' => 'nullable|string'
        ]);

        try {
            $assignment = $vehicle->unassignDriver(Auth::id(), $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Driver unassigned from vehicle successfully!',
                'data' => $assignment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to unassign driver. Please try again"
            ], 422);
        }
    }

    /**
     * Get vehicle's transport history
     */
    public function transportHistory(Vehicle $vehicle, Request $request)
    {
        $query = $vehicle->transportRequests()->with(['patient', 'driver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('scheduled_time', [$request->date_from, $request->date_to]);
        }

        $transports = $query->orderBy('scheduled_time', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transports->items(),
            'pagination' => [
                'current_page' => $transports->currentPage(),
                'last_page' => $transports->lastPage(),
                'per_page' => $transports->perPage(),
                'total' => $transports->total()
            ]
        ]);
    }


    /**
     * Delete a vehicle (soft delete)
     */
    public function destroy(Vehicle $vehicle)
    {
        try {
            DB::beginTransaction();

            // Check if vehicle has a current driver with active transport requests
            $activeTransports = 0;
            
            if ($vehicle->current_driver) {
                // Check if the assigned driver has any active transport requests
                $activeTransports = TransportRequest::where('driver_id', $vehicle->current_driver->id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->count();
                    
                if ($activeTransports > 0) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot delete vehicle. The assigned driver has {$activeTransports} active transport request(s). Please complete or cancel them first."
                    ], 422);
                }
                
                // Unassign the driver before deleting
                try {
                    $vehicle->unassignDriver(Auth::id(), 'Vehicle deleted by system');
                } catch (\Exception $e) {
                    \Log::warning('Failed to unassign driver during vehicle deletion', [
                        'vehicle_id' => $vehicle->id,
                        'driver_id' => $vehicle->current_driver->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Check if vehicle is currently in use
            if ($vehicle->status === 'in_use') {
                \Log::warning('Deleting vehicle that is in use', [
                    'vehicle_id' => $vehicle->id,
                    'status' => $vehicle->status,
                    'registration' => $vehicle->registration_number
                ]);
            }

            // Perform soft delete
            $vehicle->delete();

            DB::commit();

            \Log::info('Vehicle deleted successfully', [
                'vehicle_id' => $vehicle->id,
                'registration' => $vehicle->registration_number,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            \Log::error('Error deleting vehicle', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vehicle.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for vehicles
     */
    public function dashboard()
    {
        try {
            $stats = [
                'total_vehicles' => Vehicle::count(),
                'active_vehicles' => Vehicle::active()->count(),
                'available_vehicles' => Vehicle::available()->count(),
                'assigned_vehicles' => Vehicle::assigned()->count(),
                'unassigned_vehicles' => Vehicle::unassigned()->count(),
                'vehicles_in_maintenance' => Vehicle::byStatus('maintenance')->count(),
                'vehicles_out_of_service' => Vehicle::byStatus('out_of_service')->count(),
                
                'by_type' => Vehicle::select('vehicle_type', DB::raw('count(*) as count'))
                    ->groupBy('vehicle_type')
                    ->pluck('count', 'vehicle_type'),
                    
                'by_status' => Vehicle::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                    
                'insurance_expiring' => Vehicle::insuranceExpiring(30)->count(),
                'registration_expiring' => Vehicle::registrationExpiring(30)->count(),
                'insurance_expired' => Vehicle::insuranceExpired()->count(),
                'registration_expired' => Vehicle::registrationExpired()->count(),
                
                'usage_metrics' => [
                    'total_trips' => 0, // Set to 0 for now, will fix after checking table structure
                    'average_mileage' => Vehicle::whereNotNull('mileage')->avg('mileage') ?? 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Dashboard statistics error: ' . $e->getMessage());
            
            // Return default stats on error
            return response()->json([
                'success' => true,
                'data' => [
                    'total_vehicles' => 0,
                    'active_vehicles' => 0,
                    'available_vehicles' => 0,
                    'assigned_vehicles' => 0,
                    'unassigned_vehicles' => 0,
                    'vehicles_in_maintenance' => 0,
                    'vehicles_out_of_service' => 0,
                    'by_type' => [],
                    'by_status' => [],
                    'insurance_expiring' => 0,
                    'registration_expiring' => 0,
                    'insurance_expired' => 0,
                    'registration_expired' => 0,
                    'usage_metrics' => [
                        'total_trips' => 0,
                        'average_mileage' => 0
                    ]
                ]
            ]);
        }
    }

    /**
     * Export vehicles
     */
    public function export(Request $request)
    {
        $query = Vehicle::with(['currentDriver']);

        // Apply same filters as index
        if ($request->filled('vehicle_type')) {
            $query->byType($request->vehicle_type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $vehicles = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Vehicle ID',
            'Registration Number',
            'Type',
            'Color',
            'Make',
            'Model',
            'Year',
            'Status',
            'Active',
            'Assigned Driver',
            'Insurance Expiry',
            'Registration Expiry',
            'Created Date'
        ];

        foreach ($vehicles as $vehicle) {
            $csvData[] = [
                $vehicle->vehicle_id,
                $vehicle->registration_number,
                ucfirst($vehicle->vehicle_type),
                $vehicle->vehicle_color,
                $vehicle->make ?? '',
                $vehicle->model ?? '',
                $vehicle->year ?? '',
                $vehicle->status_label,
                $vehicle->is_active ? 'Yes' : 'No',
                $vehicle->currentDriver ? $vehicle->currentDriver->full_name : 'Not assigned',
                $vehicle->insurance_expiry ? $vehicle->insurance_expiry->format('Y-m-d') : '',
                $vehicle->registration_expiry ? $vehicle->registration_expiry->format('Y-m-d') : '',
                $vehicle->created_at->format('Y-m-d')
            ];
        }

        $filename = 'vehicles_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get vehicles with expiring insurance
     */
    public function expiringInsurance(Request $request)
    {
        $days = $request->get('days', 30);
        
        $vehicles = Vehicle::active()
            ->insuranceExpiring($days)
            ->orderBy('insurance_expiry')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
            'message' => count($vehicles) . " vehicle(s) have insurance expiring within {$days} days."
        ]);
    }

    /**
     * Get vehicles with expiring registration
     */
    public function expiringRegistration(Request $request)
    {
        $days = $request->get('days', 30);
        
        $vehicles = Vehicle::active()
            ->registrationExpiring($days)
            ->orderBy('registration_expiry')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
            'message' => count($vehicles) . " vehicle(s) have registration expiring within {$days} days."
        ]);
    }
}