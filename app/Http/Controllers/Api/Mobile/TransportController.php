<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\TransportRequest;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransportController extends Controller
{
    /**
     * Get transport requests for authenticated user (nurse or patient)
     * GET /api/mobile/transport-requests
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!in_array($user->role, ['nurse', 'patient'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses and patients can access this endpoint.'
                ], 403);
            }

            $perPage = min(max((int) $request->get('per_page', 15), 5), 50);

            $query = TransportRequest::with([
                'patient:id,first_name,last_name',
                'requestedBy:id,first_name,last_name',
                'driver.currentVehicle'
            ]);

            // Filter based on user role
            if ($user->role === 'nurse') {
                // Nurses see requests they created
                $query->where('requested_by_id', $user->id);
            } else {
                // Patients see requests for themselves
                $query->where('patient_id', $user->id);
            }

            // Apply filters
            $this->applyFilters($query, $request);

            $query->orderBy('scheduled_time', 'desc');

            $transports = $query->paginate($perPage);

            $transformedData = $transports->map(function($transport) {
                return $this->transformTransportRequest($transport);
            });

            // Get status counts based on role
            $statusCountsQuery = TransportRequest::query();
            if ($user->role === 'nurse') {
                $statusCountsQuery->where('requested_by_id', $user->id);
            } else {
                $statusCountsQuery->where('patient_id', $user->id);
            }

            $statusCounts = $statusCountsQuery
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'counts' => [
                    'requested' => $statusCounts['requested'] ?? 0,
                    'assigned' => $statusCounts['assigned'] ?? 0,
                    'in_progress' => $statusCounts['in_progress'] ?? 0,
                    'completed' => $statusCounts['completed'] ?? 0,
                    'cancelled' => $statusCounts['cancelled'] ?? 0,
                    'total' => array_sum($statusCounts),
                ],
                'total' => $transports->total(),
                'current_page' => $transports->currentPage(),
                'last_page' => $transports->lastPage(),
                'per_page' => $transports->perPage(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching transport requests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transport requests.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get a specific transport request
     * GET /api/mobile/transport-requests/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!in_array($user->role, ['nurse', 'patient'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses and patients can access this endpoint.'
                ], 403);
            }

            $query = TransportRequest::with([
                'patient',
                'requestedBy',
                'driver.currentVehicle'
            ])->where('id', $id);

            // Filter based on user role
            if ($user->role === 'nurse') {
                $query->where('requested_by_id', $user->id);
            } else {
                $query->where('patient_id', $user->id);
            }

            $transport = $query->first();

            if (!$transport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transport request not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformTransportRequest($transport)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching transport request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transport request.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a new transport request
     * POST /api/mobile/transport-requests
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Determine if user is nurse or patient
            $isNurse = $user->role === 'nurse';
            $isPatient = $user->role === 'patient';
            
            if (!$isNurse && !$isPatient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses and patients can create transport requests.'
                ], 403);
            }

            // Validation rules differ based on role
            $rules = [
                'driver_id' => 'nullable|exists:drivers,id',
                'transport_type' => 'required|in:ambulance,regular',
                'priority' => 'required|in:emergency,urgent,routine',
                'scheduled_time' => 'nullable|date|after:now',
                'pickup_location' => 'required|string|max:255',
                'pickup_address' => 'nullable|string',
                'pickup_latitude' => 'nullable|numeric|between:-90,90',
                'pickup_longitude' => 'nullable|numeric|between:-180,180',
                'destination_location' => 'required|string|max:255',
                'destination_address' => 'required|string',
                'destination_latitude' => 'nullable|numeric|between:-90,90',
                'destination_longitude' => 'nullable|numeric|between:-180,180',
                'reason' => 'nullable|string',
                'special_requirements' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
            ];
            
            // Nurses must provide patient_id, patients create for themselves
            if ($isNurse) {
                $rules['patient_id'] = 'required|exists:users,id';
            }

            $validated = $request->validate($rules);

            // Set patient_id based on role
            if ($isPatient) {
                $validated['patient_id'] = $user->id;
            }

            // Verify patient
            $patient = User::where('id', $validated['patient_id'])
                ->where('role', 'patient')
                ->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient selected.'
                ], 422);
            }

            // Verify driver if provided
            if ($request->filled('driver_id')) {
                $driver = Driver::find($request->driver_id);

                if (!$driver) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid driver selected.'
                    ], 422);
                }

                // Check if driver has active transports
                $hasActiveTransport = TransportRequest::where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->exists();

                if ($hasActiveTransport) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected driver is currently on another trip.'
                    ], 422);
                }

                // Verify driver has appropriate vehicle
                $hasAppropriateVehicle = $driver->currentVehicle && 
                    $driver->currentVehicle->vehicle_type === $validated['transport_type'] &&
                    $driver->currentVehicle->is_active &&
                    $driver->currentVehicle->is_available;

                if (!$hasAppropriateVehicle) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected driver does not have an appropriate vehicle.'
                    ], 422);
                }
            }

            DB::beginTransaction();

            try {
                $transportRequest = TransportRequest::create([
                    'patient_id' => $validated['patient_id'],
                    'requested_by_id' => $user->id, // Can be nurse or patient
                    'driver_id' => $validated['driver_id'] ?? null,
                    'transport_type' => $validated['transport_type'],
                    'priority' => $validated['priority'],
                    'scheduled_time' => $validated['scheduled_time'],
                    'pickup_location' => $validated['pickup_location'],
                    'pickup_address' => $validated['pickup_address'],
                    'pickup_latitude' => $validated['pickup_latitude'] ?? null,
                    'pickup_longitude' => $validated['pickup_longitude'] ?? null,
                    'destination_location' => $validated['destination_location'],
                    'destination_address' => $validated['destination_address'],
                    'destination_latitude' => $validated['destination_latitude'] ?? null,
                    'destination_longitude' => $validated['destination_longitude'] ?? null,
                    'reason' => $validated['reason'],
                    'special_requirements' => $validated['special_requirements'] ?? null,
                    'contact_person' => $validated['contact_person'] ?? null,
                    'status' => $validated['driver_id'] ? 'assigned' : 'requested'
                ]);

                // Calculate estimated cost
                $estimatedCost = $transportRequest->calculateEstimatedCost();
                $transportRequest->update(['estimated_cost' => $estimatedCost]);

                // Auto-assign for emergency without driver
                if ($validated['priority'] === 'emergency' && !$validated['driver_id']) {
                    $this->autoAssignDriver($transportRequest);
                }

                DB::commit();

                $transportRequest->load(['patient', 'requestedBy', 'driver.currentVehicle']);

                $message = $isPatient 
                    ? 'Your transport request has been submitted successfully!'
                    : ($validated['driver_id'] 
                        ? 'Transport request created and driver assigned successfully!' 
                        : 'Transport request created successfully!');

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $this->transformTransportRequest($transportRequest)
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating transport request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transport request.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get available drivers
     * GET /api/mobile/transport-requests/drivers/available
     */
    public function getAvailableDrivers(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Allow both nurses and patients to view available drivers
            if (!in_array($user->role, ['nurse', 'patient'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses and patients can access this endpoint.'
                ], 403);
            }

            $transportType = $request->query('transport_type', 'regular');

            \Log::info('🛞 Transport type: ' . $transportType);
            \Log::info('🌐 Endpoint: ' . $request->fullUrl());

            $drivers = Driver::available()
                ->with(['currentVehicle', 'activeVehicleAssignment.vehicle'])
                ->whereHas('currentVehicle', function($query) use ($transportType) {
                    $query->where('vehicles.vehicle_type', $transportType)
                        ->where('vehicles.is_active', true)
                        ->where('vehicles.is_available', true);
                })
                ->whereDoesntHave('transportRequests', function($query) {
                    $query->whereIn('status', ['assigned', 'in_progress']);
                })
                ->orderBy('average_rating', 'desc')
                ->get()
                ->map(function($driver) {
                    $vehicle = $driver->currentVehicle;

                    return [
                        'id' => $driver->id,
                        'name' => $driver->full_name,
                        'phone' => $driver->phone,
                        'vehicle_type' => $vehicle ? $vehicle->vehicle_type : null,
                        'vehicle_number' => $vehicle ? $vehicle->registration_number : null,
                        'vehicle_model' => $vehicle ? ($vehicle->make . ' ' . $vehicle->model) : null,
                        'vehicle_color' => $vehicle ? $vehicle->vehicle_color : null,
                        'average_rating' => $driver->average_rating ? round($driver->average_rating, 1) : null,
                        'total_trips' => $driver->total_trips ?? 0,
                        'is_available' => true,
                        'status' => 'available',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $drivers,
                'total' => $drivers->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error fetching available drivers: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available drivers.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Transform transport request for API response
     */
    private function transformTransportRequest($transport): array
    {
        $driver = $transport->driver;
        $vehicle = $driver ? $driver->currentVehicle : null;

        return [
            'id' => $transport->id,
            'patient_id' => $transport->patient_id,
            'patient_name' => $transport->patient 
                ? $transport->patient->first_name . ' ' . $transport->patient->last_name 
                : 'Unknown Patient',
            'transport_type' => $transport->transport_type,
            'type_label' => ucfirst($transport->transport_type),
            'priority' => $transport->priority,
            'priority_label' => ucfirst($transport->priority),
            'status' => $transport->status,
            'status_label' => $this->getStatusLabel($transport->status),
            'scheduled_time' => $transport->scheduled_time 
                ? $transport->scheduled_time->toIso8601String() 
                : null,
            'pickup_location' => $transport->pickup_location,
            'pickup_address' => $transport->pickup_address,
            'destination_location' => $transport->destination_location,
            'destination_address' => $transport->destination_address,
            'distance_km' => $transport->distance_km,
            'estimated_duration_minutes' => $transport->estimated_duration_minutes,
            'reason' => $transport->reason,
            'special_requirements' => $transport->special_requirements,
            'contact_person' => $transport->contact_person,
            'driver' => $driver ? [
                'id' => $driver->id,
                'name' => $driver->full_name,
                'phone' => $driver->phone,
                'vehicle_type' => $vehicle ? $vehicle->vehicle_type : null,
                'vehicle_number' => $vehicle ? $vehicle->registration_number : null,
                'vehicle_model' => $vehicle ? ($vehicle->make . ' ' . $vehicle->model) : null,
                'vehicle_color' => $vehicle ? $vehicle->vehicle_color : null,
                'average_rating' => $driver->average_rating,
            ] : null,
            'estimated_cost' => $transport->estimated_cost,
            'actual_cost' => $transport->actual_cost,
            'rating' => $transport->rating,
            'feedback' => $transport->feedback,
            'created_at' => $transport->created_at->toIso8601String(),
            'updated_at' => $transport->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel($status): string
    {
        $labels = [
            'requested' => 'Requested',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Apply filters to transport query
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('type')) {
            $query->where('transport_type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pickup_location', 'like', "%{$search}%")
                  ->orWhere('destination_location', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
    }

    /**
     * Auto-assign driver for emergency requests
     */
    private function autoAssignDriver(TransportRequest $transportRequest): void
    {
        $availableDriver = Driver::available()
            ->whereHas('currentVehicle', function($query) use ($transportRequest) {
                $query->where('vehicle_type', $transportRequest->transport_type);
            })
            ->orderBy('average_rating', 'desc')
            ->first();

        if ($availableDriver) {
            $transportRequest->assignDriver($availableDriver->id);
        }
    }
}