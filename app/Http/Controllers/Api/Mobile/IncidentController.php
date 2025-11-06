<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use App\Models\User;
use App\Models\CarePlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class IncidentController extends Controller
{
    /**
     * Get incident reports for the authenticated nurse
     * GET /api/mobile/incidents
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $perPage = min(max((int) $request->get('per_page', 15), 5), 50);

            $query = IncidentReport::with(['patient:id,first_name,last_name', 'reporter:id,first_name,last_name'])
                ->where('reported_by', $nurse->id)
                ->orderBy('incident_date', 'desc')
                ->orderBy('incident_time', 'desc');

            // Apply filters
            $this->applyFilters($query, $request);

            $incidents = $query->paginate($perPage);

            $data = $incidents->map(function ($incident) {
                return $incident->toApiArray();
            });

            // Get status counts
            $statusCounts = IncidentReport::where('reported_by', $nurse->id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => $data->isEmpty() ? 'No incident reports found.' : null,
                'data' => $data,
                'counts' => [
                    'pending' => $statusCounts['pending'] ?? 0,
                    'under_review' => $statusCounts['under_review'] ?? 0,
                    'resolved' => $statusCounts['resolved'] ?? 0,
                    'closed' => $statusCounts['closed'] ?? 0,
                    'total' => array_sum($statusCounts),
                ],
                'total' => $incidents->total(),
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'per_page' => $incidents->perPage(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching incident reports: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve incident reports.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get a specific incident report
     * GET /api/mobile/incidents/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $nurse = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $incident = IncidentReport::with(['patient', 'reporter'])
                ->where('id', $id)
                ->where('reported_by', $nurse->id)
                ->first();

            if (!$incident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident report not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $incident->toApiArray()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching incident report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch incident report.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a new incident report
     * POST /api/mobile/incidents
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can create incident reports.'
                ], 403);
            }

            $validated = $request->validate([
                // Section 1: General Information
                'report_date' => 'required|date',
                'incident_date' => 'required|date|before_or_equal:today',
                'incident_time' => 'required|date_format:H:i',
                'incident_location' => 'nullable|string|max:255',
                'incident_type' => 'required|in:fall,medication_error,equipment_failure,injury,other',
                'incident_type_other' => 'required_if:incident_type,other|nullable|string|max:255',
                
                // Section 2: Person(s) Involved
                'patient_id' => 'required|exists:users,id',
                'patient_age' => 'nullable|integer|min:0|max:150',
                'patient_sex' => 'nullable|in:M,F',
                'client_id_case_no' => 'nullable|string|max:255',
                'staff_family_involved' => 'nullable|string|max:255',
                'staff_family_role' => 'nullable|in:nurse,family,other',
                'staff_family_role_other' => 'required_if:staff_family_role,other|nullable|string|max:255',
                
                // Section 3: Description
                'incident_description' => 'required|string|max:2000',
                
                // Section 4: Immediate Actions
                'first_aid_provided' => 'boolean',
                'first_aid_description' => 'required_if:first_aid_provided,true|nullable|string|max:1000',
                'care_provider_name' => 'nullable|string|max:255',
                'transferred_to_hospital' => 'boolean',
                'hospital_transfer_details' => 'required_if:transferred_to_hospital,true|nullable|string|max:1000',
                
                // Section 5: Witnesses
                'witness_names' => 'nullable|string|max:1000',
                'witness_contacts' => 'nullable|string|max:1000',
                
                // Section 6: Follow-Up
                'reported_to_supervisor' => 'nullable|string|max:255',
                'corrective_preventive_actions' => 'nullable|string|max:1000',
                
                // Additional tracking
                'severity' => 'nullable|in:low,medium,high,critical',
                'follow_up_required' => 'boolean',
                'follow_up_date' => 'nullable|date|after:today',
            ]);

            // Verify patient
            $patient = User::where('id', $request->patient_id)
                ->where('role', 'patient')
                ->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient selected.'
                ], 422);
            }

            // Prepare incident data
            $incidentData = [
                'report_date' => $request->report_date,
                'incident_date' => $request->incident_date,
                'incident_time' => $request->incident_time,
                'incident_location' => $request->incident_location,
                'incident_type' => $request->incident_type,
                'incident_type_other' => $request->incident_type_other,
                'patient_id' => $request->patient_id,
                'patient_age' => $request->patient_age ?? $patient->age ?? null,
                'patient_sex' => $request->patient_sex ?? strtoupper(substr($patient->gender ?? 'M', 0, 1)),
                'client_id_case_no' => $request->client_id_case_no,
                'staff_family_involved' => $request->staff_family_involved,
                'staff_family_role' => $request->staff_family_role,
                'staff_family_role_other' => $request->staff_family_role_other,
                'incident_description' => $request->incident_description,
                'first_aid_provided' => $request->first_aid_provided ?? false,
                'first_aid_description' => $request->first_aid_description,
                'care_provider_name' => $request->care_provider_name,
                'transferred_to_hospital' => $request->transferred_to_hospital ?? false,
                'hospital_transfer_details' => $request->hospital_transfer_details,
                'witness_names' => $request->witness_names,
                'witness_contacts' => $request->witness_contacts,
                'reported_to_supervisor' => $request->reported_to_supervisor,
                'corrective_preventive_actions' => $request->corrective_preventive_actions,
                'reported_by' => $nurse->id,
                'reported_at' => now(),
                'status' => 'pending',
                'severity' => $request->severity ?? 'medium',
                'follow_up_required' => $request->follow_up_required ?? false,
                'follow_up_date' => $request->follow_up_date,
            ];

            $incident = IncidentReport::create($incidentData);
            
            $newIncident = IncidentReport::with(['reporter', 'patient'])->find($incident->id);

            return response()->json([
                'success' => true,
                'message' => 'Incident report created successfully.',
                'data' => $newIncident->toApiArray()
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating incident report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create incident report.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get patients assigned to nurse for incident dropdown
     * GET /api/mobile/incidents/patients/list
     */
    public function getPatients(): JsonResponse
    {
        try {
            $nurse = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            // Get patient IDs from active care plans
            $patientIds = CarePlan::where(function($query) use ($nurse) {
                    $query->where('primary_nurse_id', $nurse->id)
                        ->orWhere('secondary_nurse_id', $nurse->id);
                })
                ->where('status', 'active')
                ->pluck('patient_id')
                ->unique();

            $patients = User::whereIn('id', $patientIds)
                ->where('role', 'patient')
                ->where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'date_of_birth', 'gender')
                ->orderBy('first_name')
                ->get()
                ->map(function ($patient) {
                    $age = $patient->date_of_birth 
                        ? \Carbon\Carbon::parse($patient->date_of_birth)->age 
                        : null;
                    
                    return [
                        'id' => $patient->id,
                        'name' => $patient->first_name . ' ' . $patient->last_name,
                        'age' => $age,
                        'gender' => $patient->gender,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => $patients->isEmpty() ? 'No patients assigned to you yet.' : null,
                'data' => $patients
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patients for incident: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patients.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Apply filters to incident query
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('incident_date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('incident_type', 'like', "%{$search}%")
                  ->orWhere('incident_description', 'like', "%{$search}%")
                  ->orWhere('incident_location', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
    }
}