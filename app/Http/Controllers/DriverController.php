<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\DriverVehicleAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers
     */
    public function index(Request $request)
    {
        $query = Driver::query()->with(['currentVehicle', 'activeVehicleAssignment']);

        // Apply filters
        if ($request->filled('active')) {
            if ($request->active === 'active') {
                $query->active();
            } elseif ($request->active === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('suspended')) {
            if ($request->suspended === 'true') {
                $query->suspended();
            } elseif ($request->suspended === 'false') {
                $query->where('is_suspended', false);
            }
        }

        if ($request->filled('vehicle_assigned')) {
            if ($request->vehicle_assigned === 'true') {
                $query->withVehicle();
            } elseif ($request->vehicle_assigned === 'false') {
                $query->withoutVehicle();
            }
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sort by creation date
        $query->orderBy('created_at', 'desc');

        $drivers = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $drivers->items(),
            'pagination' => [
                'current_page' => $drivers->currentPage(),
                'last_page' => $drivers->lastPage(),
                'per_page' => $drivers->perPage(),
                'total' => $drivers->total()
            ]
        ]);
    }

    /**
     * Store a new driver
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:drivers',
            'email' => 'nullable|email|unique:drivers',
            'date_of_birth' => 'required|date|before:today',
            'notes' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        // Validate minimum age (18 years)
        $age = Carbon::parse($request->date_of_birth)->age;
        if ($age < 18) {
            return response()->json([
                'success' => false,
                'message' => 'Driver must be at least 18 years old.',
                'errors' => ['date_of_birth' => ['Driver must be at least 18 years old.']]
            ], 422);
        }

        DB::beginTransaction();

        try {
            $driverData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'date_of_birth' => $request->date_of_birth,
                'notes' => $request->notes,
                'is_active' => true
            ];

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('drivers/avatars', 'public');
                $driverData['avatar'] = $avatarPath;
            }

            $driver = Driver::create($driverData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver added successfully!',
                'data' => $driver->load(['currentVehicle', 'activeVehicleAssignment'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add driver.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified driver
     */
    public function show(Driver $driver)
    {
        $driver->load(['suspendedBy', 'currentVehicle', 'activeVehicleAssignment']);
        
        // Add performance metrics
        $driver->performance_metrics = $driver->getPerformanceMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $driver
        ]);
    }

    /**
     * Update the specified driver
     */
    public function update(Request $request, Driver $driver)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:drivers,phone,' . $driver->id,
            'email' => 'nullable|email|unique:drivers,email,' . $driver->id,
            'date_of_birth' => 'required|date|before:today',
            'notes' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $updateData = $request->only([
            'first_name',
            'last_name', 
            'phone',
            'email',
            'date_of_birth',
            'notes'
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
                            \Log::info("Has Photo here");

            if ($driver->avatar) {
                \Log::info("Deleteing here");
                Storage::disk('public')->delete($driver->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('drivers/avatars', 'public');
        }

        $driver->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Driver updated successfully!',
            'data' => $driver->load(['currentVehicle', 'activeVehicleAssignment'])
        ]);
    }

    /**
     * Suspend a driver
     */
    public function suspend(Request $request, Driver $driver)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        if ($driver->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Driver is already suspended.'
            ], 422);
        }

        $driver->suspend($request->reason, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Driver suspended successfully!'
        ]);
    }

    /**
     * Reactivate a suspended driver
     */
    public function reactivate(Driver $driver)
    {
        if (!$driver->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Driver is not suspended.'
            ], 422);
        }

        $driver->reactivate();

        return response()->json([
            'success' => true,
            'message' => 'Driver reactivated successfully!'
        ]);
    }

    /**
     * Get available drivers (not assigned to any vehicle)
     */
    public function available(Request $request)
    {
        $drivers = Driver::active()
            ->where('is_suspended', false)
            ->withoutVehicle()
            ->orderBy('average_rating', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    /**
     * Assign a vehicle to a driver
     */
    public function assignVehicle(Request $request, Driver $driver)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'notes' => 'nullable|string'
        ]);

        try {
            $assignment = $driver->assignVehicle(
                $request->vehicle_id,
                Auth::id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Vehicle assigned to driver successfully!',
                'data' => $assignment->load(['driver', 'vehicle'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to assign vehicle. Please try again"
            ], 422);
        }
    }

    /**
     * Unassign vehicle from driver
     */
    public function unassignVehicle(Request $request, Driver $driver)
    {
        $request->validate([
            'reason' => 'nullable|string'
        ]);

        try {
            $assignment = $driver->unassignVehicle(Auth::id(), $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle unassigned from driver successfully!',
                'data' => $assignment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to unassigned vehicle. Please try again"
            ], 422);
        }
    }

    /**
     * Get driver's transport history
     */
    public function transportHistory(Driver $driver, Request $request)
    {
        $query = $driver->transportRequests()->with(['patient']);

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
     * Get dashboard statistics for drivers
     */
    public function dashboard()
    {
        $stats = [
            'total_drivers' => Driver::count(),
            'active_drivers' => Driver::active()->count(),
            'suspended_drivers' => Driver::suspended()->count(),
            'drivers_with_vehicles' => Driver::withVehicle()->count(),
            'drivers_without_vehicles' => Driver::withoutVehicle()->count(),
            
            'performance_metrics' => [
                'average_rating' => Driver::whereNotNull('average_rating')->avg('average_rating'),
                'total_trips' => Driver::sum('total_trips'),
                'completion_rate' => $this->calculateOverallCompletionRate()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Export drivers
     */
    public function export(Request $request)
    {
        $query = Driver::with(['currentVehicle']);

        // Apply same filters as index
        if ($request->filled('active')) {
            if ($request->active === 'active') {
                $query->active();
            } elseif ($request->active === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $drivers = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Driver ID',
            'Name',
            'Phone',
            'Email',
            'Age',
            'Active',
            'Suspended',
            'Average Rating',
            'Total Trips',
            'Assigned Vehicle',
            'Created Date'
        ];

        foreach ($drivers as $driver) {
            $csvData[] = [
                $driver->driver_id,
                $driver->full_name,
                $driver->phone,
                $driver->email ?? '',
                $driver->age,
                $driver->is_active ? 'Yes' : 'No',
                $driver->is_suspended ? 'Yes' : 'No',
                $driver->average_rating ?? '',
                $driver->total_trips,
                $driver->currentVehicle ? $driver->currentVehicle->registration_number : 'Not assigned',
                $driver->created_at->format('Y-m-d')
            ];
        }

        $filename = 'drivers_' . now()->format('Y_m_d_H_i_s') . '.csv';

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
     * Upload driver avatar
     */
    public function uploadAvatar(Request $request, Driver $driver)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048'
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($driver->avatar) {
                Storage::disk('public')->delete($driver->avatar);
            }

            $path = $request->file('avatar')->store('drivers/avatars', 'public');
            $driver->update(['avatar' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully!',
                'avatar_url' => $driver->avatar_url
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file uploaded.'
        ], 422);
    }

    /**
     * Calculate overall completion rate
     */
    private function calculateOverallCompletionRate()
    {
        $totalTrips = Driver::sum('total_trips');
        $completedTrips = Driver::sum('completed_trips');
        
        if ($totalTrips === 0) return 0;
        
        return round(($completedTrips / $totalTrips) * 100, 2);
    }
}