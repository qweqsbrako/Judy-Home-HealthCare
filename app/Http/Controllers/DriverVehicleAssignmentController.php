<?php

namespace App\Http\Controllers;

use App\Models\DriverVehicleAssignment;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverVehicleAssignmentController extends Controller
{
    /**
     * Display a listing of assignments
     */
    public function index(Request $request)
    {
        $query = DriverVehicleAssignment::with(['driver', 'vehicle', 'assignedBy', 'unassignedBy']);

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('active')) {
            if ($request->active === 'true') {
                $query->active();
            } elseif ($request->active === 'false') {
                $query->inactive();
            }
        }

        if ($request->filled('driver_id')) {
            $query->byDriver($request->driver_id);
        }

        if ($request->filled('vehicle_id')) {
            $query->byVehicle($request->vehicle_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->assignedBetween($request->date_from, $request->date_to);
        }

        if ($request->filled('temporary')) {
            if ($request->temporary === 'true') {
                $query->temporary();
            }
        }

        $query->orderBy('assigned_at', 'desc');

        $assignments = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $assignments->items(),
            'pagination' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total()
            ]
        ]);
    }

    /**
     * Create a new assignment
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'assignment_notes' => 'nullable|string',
            'status' => 'nullable|in:active,temporary',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from'
        ]);

        // Check if driver is available
        $driver = Driver::find($request->driver_id);
        if (!$driver->is_active || $driver->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Driver is not available for assignment.'
            ], 422);
        }

        // Check if vehicle is available
        $vehicle = Vehicle::find($request->vehicle_id);
        if (!$vehicle->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle is not available for assignment.'
            ], 422);
        }

        // Check for existing active assignments
        $existingDriverAssignment = DriverVehicleAssignment::where('driver_id', $request->driver_id)
                                                           ->where('is_active', true)
                                                           ->first();
        if ($existingDriverAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Driver is already assigned to another vehicle.'
            ], 422);
        }

        $existingVehicleAssignment = DriverVehicleAssignment::where('vehicle_id', $request->vehicle_id)
                                                            ->where('is_active', true)
                                                            ->first();
        if ($existingVehicleAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle is already assigned to another driver.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $assignment = DriverVehicleAssignment::create([
                'driver_id' => $request->driver_id,
                'vehicle_id' => $request->vehicle_id,
                'assigned_at' => now(),
                'assigned_by' => Auth::id(),
                'assignment_notes' => $request->assignment_notes,
                'status' => $request->status ?? 'active',
                'effective_from' => $request->effective_from,
                'effective_until' => $request->effective_until,
                'is_active' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignment created successfully!',
                'data' => $assignment->load(['driver', 'vehicle', 'assignedBy'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified assignment
     */
    public function show(DriverVehicleAssignment $assignment)
    {
        $assignment->load(['driver', 'vehicle', 'assignedBy', 'unassignedBy']);
        
        // Add assignment summary
        $assignment->summary = $assignment->getAssignmentSummary();
        
        return response()->json([
            'success' => true,
            'data' => $assignment
        ]);
    }

    /**
     * Update the specified assignment
     */
    public function update(Request $request, DriverVehicleAssignment $assignment)
    {
        $request->validate([
            'assignment_notes' => 'nullable|string',
            'effective_until' => 'nullable|date|after:' . $assignment->assigned_at,
            'unassignment_reason' => 'nullable|string'
        ]);

        $updateData = [];

        if ($request->has('assignment_notes')) {
            $updateData['assignment_notes'] = $request->assignment_notes;
        }

        if ($request->has('effective_until')) {
            $updateData['effective_until'] = $request->effective_until;
        }

        if (!empty($updateData)) {
            $assignment->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully!',
            'data' => $assignment->load(['driver', 'vehicle', 'assignedBy', 'unassignedBy'])
        ]);
    }

    /**
     * Deactivate an assignment
     */
    public function deactivate(Request $request, DriverVehicleAssignment $assignment)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        if (!$assignment->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment is already inactive.'
            ], 422);
        }

        $assignment->deactivate($request->reason, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Assignment deactivated successfully!',
            'data' => $assignment->load(['driver', 'vehicle', 'assignedBy', 'unassignedBy'])
        ]);
    }

    /**
     * Activate an assignment
     */
    public function activate(DriverVehicleAssignment $assignment)
    {
        if ($assignment->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment is already active.'
            ], 422);
        }

        // Check for conflicts
        $existingDriverAssignment = DriverVehicleAssignment::where('driver_id', $assignment->driver_id)
                                                           ->where('is_active', true)
                                                           ->where('id', '!=', $assignment->id)
                                                           ->first();
        if ($existingDriverAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Driver is already assigned to another vehicle.'
            ], 422);
        }

        $existingVehicleAssignment = DriverVehicleAssignment::where('vehicle_id', $assignment->vehicle_id)
                                                            ->where('is_active', true)
                                                            ->where('id', '!=', $assignment->id)
                                                            ->first();
        if ($existingVehicleAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle is already assigned to another driver.'
            ], 422);
        }

        $assignment->activate();

        return response()->json([
            'success' => true,
            'message' => 'Assignment activated successfully!',
            'data' => $assignment->load(['driver', 'vehicle', 'assignedBy', 'unassignedBy'])
        ]);
    }

    /**
     * Extend a temporary assignment
     */
    public function extend(Request $request, DriverVehicleAssignment $assignment)
    {
        $request->validate([
            'new_end_date' => 'required|date|after:' . $assignment->assigned_at,
            'notes' => 'nullable|string'
        ]);

        try {
            $assignment->extend($request->new_end_date, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Assignment extended successfully!',
                'data' => $assignment->load(['driver', 'vehicle', 'assignedBy', 'unassignedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Make a temporary assignment permanent
     */
    public function makePermanent(Request $request, DriverVehicleAssignment $assignment)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        try {
            $assignment->makePermanent($request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Assignment made permanent successfully!',
                'data' => $assignment->load(['driver', 'vehicle', 'assignedBy', 'unassignedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get current assignments (active)
     */
    public function current(Request $request)
    {
        $query = DriverVehicleAssignment::active()
            ->with(['driver', 'vehicle']);

        if ($request->filled('expiring_soon')) {
            $query->whereNotNull('effective_until')
                  ->where('effective_until', '<=', now()->addDays(7));
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get assignment history for a driver
     */
    public function driverHistory($driverId, Request $request)
    {
        $query = DriverVehicleAssignment::byDriver($driverId)
            ->with(['vehicle', 'assignedBy', 'unassignedBy']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $assignments->items(),
            'pagination' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total()
            ]
        ]);
    }

    /**
     * Get assignment history for a vehicle
     */
    public function vehicleHistory($vehicleId, Request $request)
    {
        $query = DriverVehicleAssignment::byVehicle($vehicleId)
            ->with(['driver', 'assignedBy', 'unassignedBy']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $assignments->items(),
            'pagination' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total()
            ]
        ]);
    }

    /**
     * Get dashboard statistics for assignments
     */
    public function dashboard()
    {
        $stats = [
            'total_assignments' => DriverVehicleAssignment::count(),
            'active_assignments' => DriverVehicleAssignment::active()->count(),
            'inactive_assignments' => DriverVehicleAssignment::inactive()->count(),
            'temporary_assignments' => DriverVehicleAssignment::temporary()->count(),
            'expiring_assignments' => DriverVehicleAssignment::temporary()
                ->whereNotNull('effective_until')
                ->where('effective_until', '<=', now()->addDays(7))
                ->count(),
            
            'unassigned_drivers' => Driver::active()
                ->where('is_suspended', false)
                ->withoutVehicle()
                ->count(),
                
            'unassigned_vehicles' => Vehicle::active()
                ->unassigned()
                ->count(),
                
            'recent_assignments' => DriverVehicleAssignment::with(['driver', 'vehicle', 'assignedBy'])
                ->orderBy('assigned_at', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Export assignments
     */
    public function export(Request $request)
    {
        $query = DriverVehicleAssignment::with(['driver', 'vehicle', 'assignedBy', 'unassignedBy']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('active')) {
            if ($request->active === 'true') {
                $query->active();
            } elseif ($request->active === 'false') {
                $query->inactive();
            }
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Assignment ID',
            'Driver',
            'Vehicle',
            'Status',
            'Assigned At',
            'Assigned By',
            'Unassigned At',
            'Unassigned By',
            'Duration',
            'Notes'
        ];

        foreach ($assignments as $assignment) {
            $csvData[] = [
                $assignment->id,
                $assignment->driver->full_name,
                $assignment->vehicle->vehicle_info,
                $assignment->status_label,
                $assignment->assigned_at->format('Y-m-d H:i:s'),
                $assignment->assignedBy->name ?? '',
                $assignment->unassigned_at ? $assignment->unassigned_at->format('Y-m-d H:i:s') : '',
                $assignment->unassignedBy->name ?? '',
                $assignment->duration,
                $assignment->assignment_notes ?? ''
            ];
        }

        $filename = 'driver_vehicle_assignments_' . now()->format('Y_m_d_H_i_s') . '.csv';

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
}