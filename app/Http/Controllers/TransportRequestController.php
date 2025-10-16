<?php

namespace App\Http\Controllers;

use App\Models\TransportRequest;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransportRequestController extends Controller
{
    public function __construct()
    {
        
    }

    /**
     * Display a listing of transport requests
     */
    public function index(Request $request)
    {
        $query = TransportRequest::with(['patient', 'requestedBy', 'driver.currentVehicle']);

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('patient_id')) {
            $query->forPatient($request->patient_id);
        }

        if ($request->filled('driver_id')) {
            $query->forDriver($request->driver_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->scheduledBetween($request->date_from, $request->date_to);
        }

        // Sort by scheduled time
        $query->orderBy('scheduled_time', 'desc');

        // Get pagination parameter (default 15)
        $perPage = $request->input('per_page', 15);
        
        $transports = $query->paginate($perPage);

        // Transform the data to include nested relationships properly
        $transformedData = $transports->through(function ($transport) {
            return [
                'id' => $transport->id,
                'patient_id' => $transport->patient_id,
                'patient_name' => $transport->patient_name,
                'patient' => $transport->patient,
                'requested_by_id' => $transport->requested_by_id,
                'requested_by_name' => $transport->requested_by_name,
                'requestedBy' => $transport->requestedBy,
                'driver_id' => $transport->driver_id,
                'driver_name' => $transport->driver_name,
                'driver' => $transport->driver,
                'transport_type' => $transport->transport_type,
                'priority' => $transport->priority,
                'status' => $transport->status,
                'status_label' => $transport->status_label,
                'priority_label' => $transport->priority_label,
                'type_label' => $transport->type_label,
                'scheduled_time' => $transport->scheduled_time,
                'pickup_location' => $transport->pickup_location,
                'pickup_address' => $transport->pickup_address,
                'destination_location' => $transport->destination_location,
                'destination_address' => $transport->destination_address,
                'reason' => $transport->reason,
                'special_requirements' => $transport->special_requirements,
                'contact_person' => $transport->contact_person,
                'estimated_cost' => $transport->estimated_cost,
                'actual_cost' => $transport->actual_cost,
                'distance_km' => $transport->distance_km,
                'rating' => $transport->rating,
                'feedback' => $transport->feedback,
                'actual_pickup_time' => $transport->actual_pickup_time,
                'actual_arrival_time' => $transport->actual_arrival_time,
                'completed_at' => $transport->completed_at,
                'cancelled_at' => $transport->cancelled_at,
                'cancellation_reason' => $transport->cancellation_reason,
                'created_at' => $transport->created_at,
                'updated_at' => $transport->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData->items(),
            'pagination' => [
                'current_page' => $transports->currentPage(),
                'last_page' => $transports->lastPage(),
                'per_page' => $transports->perPage(),
                'total' => $transports->total()
            ]
        ]);
    }

    /**
     * Store a new transport request
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'transport_type' => 'required|in:ambulance,regular',
            'priority' => 'required|in:emergency,urgent,routine',
            'scheduled_time' => 'required|date',
            'pickup_location' => 'required|string|max:255',
            'pickup_address' => 'required|string',
            'destination_location' => 'required|string|max:255',
            'destination_address' => 'required|string',
            'reason' => 'required|string',
            'special_requirements' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();

        try {
            $transportRequest = TransportRequest::create([
                'patient_id' => $request->patient_id,
                'requested_by_id' => Auth::id(),
                'transport_type' => $request->transport_type,
                'priority' => $request->priority,
                'scheduled_time' => $request->scheduled_time,
                'pickup_location' => $request->pickup_location,
                'pickup_address' => $request->pickup_address,
                'destination_location' => $request->destination_location,
                'destination_address' => $request->destination_address,
                'reason' => $request->reason,
                'special_requirements' => $request->special_requirements,
                'contact_person' => $request->contact_person,
                'status' => 'requested'
            ]);

            // Calculate estimated cost
            $estimatedCost = $transportRequest->calculateEstimatedCost();
            $transportRequest->update(['estimated_cost' => $estimatedCost]);

            // For emergency requests, try to auto-assign available driver
            if ($request->priority === 'emergency') {
                $this->autoAssignDriver($transportRequest);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transport request created successfully!',
                'data' => $transportRequest->load(['patient', 'requestedBy', 'driver'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transport request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transport request
     */
    public function show(TransportRequest $transportRequest)
    {
        $transportRequest->load(['patient', 'requestedBy', 'driver.currentVehicle']);

        return response()->json([
            'success' => true,
            'data' => $transportRequest
        ]);
    }

    /**
     * Update the specified transport request
     */
    public function update(Request $request, TransportRequest $transportRequest)
    {
        // Check if transport can be modified
        if (!$transportRequest->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'Transport request cannot be modified in its current status.'
            ], 422);
        }

        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'transport_type' => 'required|in:ambulance,regular',
            'priority' => 'required|in:emergency,urgent,routine',
            'scheduled_time' => 'required|date',
            'pickup_location' => 'required|string|max:255',
            'pickup_address' => 'required|string',
            'destination_location' => 'required|string|max:255',
            'destination_address' => 'required|string',
            'reason' => 'required|string',
            'special_requirements' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255'
        ]);

        $transportRequest->update($request->only([
            'patient_id',
            'transport_type',
            'priority',
            'scheduled_time',
            'pickup_location',
            'pickup_address',
            'destination_location',
            'destination_address',
            'reason',
            'special_requirements',
            'contact_person'
        ]));

        // Recalculate estimated cost
        $estimatedCost = $transportRequest->calculateEstimatedCost();
        $transportRequest->update(['estimated_cost' => $estimatedCost]);

        return response()->json([
            'success' => true,
            'message' => 'Transport request updated successfully!',
            'data' => $transportRequest->load(['patient', 'requestedBy', 'driver'])
        ]);
    }

    /**
     * Cancel a transport request
     */
    public function cancel(Request $request, TransportRequest $transportRequest)
    {
        if (!$transportRequest->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Transport request cannot be cancelled in its current status.'
            ], 422);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $transportRequest->cancelTransport($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Transport request cancelled successfully!'
        ]);
    }

    /**
     * Assign a driver to transport request
     */
    public function assignDriver(Request $request, TransportRequest $transportRequest)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id'
        ]);

        $driver = Driver::find($request->driver_id);

        if (!$driver->canAcceptNewTransport()) {
            return response()->json([
                'success' => false,
                'message' => 'Driver is not available for new transports.'
            ], 422);
        }

        if ($transportRequest->status !== 'requested') {
            return response()->json([
                'success' => false,
                'message' => 'Transport request is not in a state to assign driver.'
            ], 422);
        }

        $transportRequest->assignDriver($request->driver_id);

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned successfully!',
            'data' => $transportRequest->load(['patient', 'requestedBy', 'driver'])
        ]);
    }

    /**
     * Start transport
     */
    public function start(TransportRequest $transportRequest)
    {
        if ($transportRequest->status !== 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'Transport must be assigned before starting.'
            ], 422);
        }

        $transportRequest->startTransport();

        return response()->json([
            'success' => true,
            'message' => 'Transport started successfully!',
            'data' => $transportRequest->load(['patient', 'requestedBy', 'driver'])
        ]);
    }

    /**
     * Complete transport
     */
    public function complete(Request $request, TransportRequest $transportRequest)
    {
        if ($transportRequest->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Transport must be in progress to complete.'
            ], 422);
        }

        $request->validate([
            'actual_cost' => 'nullable|numeric|min:0',
            'feedback' => 'nullable|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5'
        ]);

        $transportRequest->completeTransport(
            $request->actual_cost,
            $request->feedback,
            $request->rating
        );

        return response()->json([
            'success' => true,
            'message' => 'Transport completed successfully!',
            'data' => $transportRequest->load(['patient', 'requestedBy', 'driver'])
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        try {
            $today = Carbon::today();
            $thisWeek = Carbon::now()->startOfWeek();
            $thisMonth = Carbon::now()->startOfMonth();

            $stats = [
                'total_requests' => TransportRequest::count(),
                'today_requests' => TransportRequest::whereDate('created_at', $today)->count(),
                'pending_requests' => TransportRequest::pending()->count(),
                'active_transports' => TransportRequest::active()->count(),
                'completed_today' => TransportRequest::completed()->whereDate('completed_at', $today)->count(),
                
                'by_status' => TransportRequest::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                    
                'by_priority' => TransportRequest::select('priority', DB::raw('count(*) as count'))
                    ->groupBy('priority')
                    ->pluck('count', 'priority')
                    ->toArray(),
                    
                'by_type' => TransportRequest::select('transport_type', DB::raw('count(*) as count'))
                    ->groupBy('transport_type')
                    ->pluck('count', 'transport_type')
                    ->toArray(),
                    
                'weekly_trend' => TransportRequest::where('created_at', '>=', $thisWeek)
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->toArray(),
                    
                'average_rating' => round(TransportRequest::whereNotNull('rating')->avg('rating') ?? 0, 2),
                'total_distance' => round(TransportRequest::sum('distance_km') ?? 0, 2),
                'total_revenue' => round(TransportRequest::where('status', 'completed')->sum('actual_cost') ?? 0, 2)
            ];

            \Log::info('Transport Dashboard Stats:', $stats);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching transport dashboard stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
                'data' => [
                    'total_requests' => 0,
                    'today_requests' => 0,
                    'pending_requests' => 0,
                    'active_transports' => 0,
                    'completed_today' => 0,
                ]
            ], 500);
        }
    }

    /**
     * Export transport requests
     */
    public function export(Request $request)
    {
        $query = TransportRequest::with(['patient', 'requestedBy', 'driver']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $transports = $query->orderBy('scheduled_time', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'ID',
            'Patient',
            'Requested By',
            'Type',
            'Priority',
            'Status',
            'Pickup Location',
            'Destination',
            'Scheduled Time',
            'Driver',
            'Actual Cost',
            'Rating',
            'Created At'
        ];

        foreach ($transports as $transport) {
            $csvData[] = [
                $transport->id,
                $transport->patient_name,
                $transport->requested_by_name,
                $transport->type_label,
                $transport->priority_label,
                $transport->status_label,
                $transport->pickup_location,
                $transport->destination_location,
                $transport->scheduled_time ? $transport->scheduled_time->format('Y-m-d H:i:s') : '',
                $transport->driver_name ?? 'Not Assigned',
                $transport->actual_cost ?? '',
                $transport->rating ?? '',
                $transport->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'transport_requests_' . now()->format('Y_m_d_H_i_s') . '.csv';

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
     * Auto-assign driver for emergency requests
     */
    private function autoAssignDriver(TransportRequest $transportRequest)
    {
        $availableDriver = Driver::available()
            ->where('vehicle_type', $transportRequest->transport_type)
            ->orderBy('average_rating', 'desc')
            ->first();

        if ($availableDriver) {
            $transportRequest->assignDriver($availableDriver->id);
        }
    }

    /**
     * Get transport requests for current user (patient/nurse)
     */
    public function myRequests()
    {
        $user = Auth::user();
        
        $query = TransportRequest::with(['patient', 'requestedBy', 'driver']);
        
        if ($user->role === 'patient') {
            $query->forPatient($user->id);
        } else {
            $query->where('requested_by_id', $user->id);
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
}