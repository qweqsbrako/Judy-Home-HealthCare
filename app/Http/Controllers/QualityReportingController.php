<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PatientFeedback;
use App\Models\IncidentReport;
use App\Models\CarePlan;
use App\Models\TimeTracking;
use Illuminate\Http\Request;
use App\Models\MedicalAssessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QualityReportingController extends Controller
{
    /**
     * Get comprehensive quality assurance dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $timeframe = $request->get('timeframe', '30'); // days
            $dateFrom = Carbon::now()->subDays($timeframe);
            
            $data = [
                'overview' => $this->getQualityOverview($dateFrom),
                'patient_feedback_summary' => $this->getPatientFeedbackSummary($dateFrom),
                'nurse_performance_summary' => $this->getNursePerformanceSummary($dateFrom),
                'incident_reports_summary' => $this->getIncidentReportsSummary($dateFrom),
                'quality_metrics_summary' => $this->getQualityMetricsSummary($dateFrom),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quality assurance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient feedback data
     */
    public function getPatientFeedback(Request $request): JsonResponse
    {
        try {
            $query = PatientFeedback::with(['patient', 'nurse', 'carePlan'])
                ->latest();

            // Apply filters
            if ($request->has('nurse_id') && $request->nurse_id) {
                $query->where('nurse_id', $request->nurse_id);
            }

            if ($request->has('rating') && $request->rating) {
                $query->where('rating', $request->rating);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('patient', function($subQ) use ($search) {
                        $subQ->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
                    })->orWhereHas('nurse', function($subQ) use ($search) {
                        $subQ->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
                    })->orWhere('feedback_text', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $feedback = $query->paginate($perPage);

            // Transform data for frontend
            $feedback->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'patient_name' => $item->patient->first_name . ' ' . $item->patient->last_name,
                    'patient_id' => $item->patient->id,
                    'nurse_name' => $item->nurse->first_name . ' ' . $item->nurse->last_name,
                    'nurse_id' => $item->nurse->id,
                    'rating' => $item->rating,
                    'feedback_text' => $item->feedback_text,
                    'care_date' => $item->care_date,
                    'feedback_date' => $item->created_at->format('Y-m-d H:i'),
                    'feedback_category' => $item->feedback_category,
                    'would_recommend' => $item->would_recommend
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patient feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get nurse performance data
     */
    public function getNursePerformance(Request $request): JsonResponse
    {
        try {
            $timeframe = $request->get('timeframe', '30');
            $dateFrom = Carbon::now()->subDays($timeframe);

            $nurses = User::where('role', 'nurse')
                ->where('is_active', true)
                ->with(['feedback', 'incidentReports'])
                ->get();

            $performanceData = $nurses->map(function ($nurse) use ($dateFrom) {
                // Calculate performance metrics using CarePlan model directly
                $activeCarePlans = CarePlan::where(function($query) use ($nurse) {
                    $query->where('primary_nurse_id', $nurse->id)
                          ->orWhere('secondary_nurse_id', $nurse->id);
                })->where('status', 'active')->count();
                
                $totalHours = TimeTracking::where('nurse_id', $nurse->id)
                    ->where('created_at', '>=', $dateFrom)
                    ->where('status', 'completed')
                    ->sum('total_duration_minutes') / 60 ?? 0;
                
                $careSessionsCount = TimeTracking::where('nurse_id', $nurse->id)
                    ->where('created_at', '>=', $dateFrom)
                    ->where('status', 'completed')
                    ->whereNotNull('patient_id')
                    ->count();
                
                $feedback = $nurse->feedback()->where('created_at', '>=', $dateFrom);
                $avgRating = $feedback->avg('rating') ?? 0;
                $feedbackCount = $feedback->count();
                
                $incidents = $nurse->incidentReports()->where('created_at', '>=', $dateFrom)->count();
                $punctualityScore = $this->calculatePunctualityScore($nurse, $dateFrom);
                
                // Calculate overall score
                $overallScore = $this->calculateOverallScore($avgRating, $punctualityScore, $incidents);

                return [
                    'id' => $nurse->id,
                    'name' => $nurse->first_name . ' ' . $nurse->last_name,
                    'avatar_url' => $nurse->avatar_url,
                    'license_number' => $nurse->license_number,
                    'specialization' => $nurse->specialization,
                    'active_care_plans' => $activeCarePlans,
                    'care_sessions' => $careSessionsCount,
                    'total_hours' => round($totalHours, 1),
                    'avg_rating' => round($avgRating, 1),
                    'feedback_count' => $feedbackCount,
                    'incident_count' => $incidents,
                    'punctuality_score' => round($punctualityScore, 1),
                    'overall_score' => round($overallScore, 1),
                    'performance_grade' => $this->getPerformanceGrade($overallScore),
                    'last_activity' => TimeTracking::where('nurse_id', $nurse->id)->latest()->first()?->created_at?->format('Y-m-d') ?? 'No recent activity',
                ];
            });

            // Sort by overall score
            $performanceData = $performanceData->sortByDesc('overall_score')->values();

            return response()->json([
                'success' => true,
                'data' => $performanceData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nurse performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get incident reports data
     */
    public function getIncidentReports(Request $request): JsonResponse
    {
        try {
            $query = IncidentReport::with(['reporter', 'patient', 'reviewer', 'assignedTo'])
                ->latest();

            // Apply filters
            if ($request->has('severity') && $request->severity) {
                $query->where('severity', $request->severity);
            }

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('incident_type') && $request->incident_type) {
                $query->where('incident_type', $request->incident_type);
            }

            // Filter by nurse name (using staff_family_involved field)
            if ($request->has('nurse_name') && $request->nurse_name) {
                $query->where('staff_family_role', 'nurse')
                      ->where('staff_family_involved', 'like', "%{$request->nurse_name}%");
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('incident_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('incident_date', '<=', $request->date_to);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('incident_description', 'like', "%{$search}%")
                      ->orWhere('incident_location', 'like', "%{$search}%")
                      ->orWhere('staff_family_involved', 'like', "%{$search}%")
                      ->orWhereHas('patient', function($subQ) use ($search) {
                          $subQ->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
                      });
                });
            }

            $perPage = $request->get('per_page', 15);
            $incidents = $query->paginate($perPage);

            // Transform data for frontend
            $incidents->getCollection()->transform(function ($incident) {
                return [
                    'id' => $incident->id,
                    'title' => substr($incident->incident_description, 0, 60) . '...', // Create title from description
                    'description' => $incident->incident_description,
                    'incident_type' => $incident->formatted_incident_type,
                    'incident_location' => $incident->incident_location,
                    'severity' => $incident->severity,
                    'status' => $incident->status,
                    'incident_date' => $incident->incident_date->format('Y-m-d'),
                    'incident_time' => \Carbon\Carbon::parse($incident->incident_time)->format('H:i'),
                    'incident_datetime' => $incident->incident_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($incident->incident_time)->format('H:i'),
                    'patient_name' => $incident->patient ? $incident->patient->first_name . ' ' . $incident->patient->last_name : 'N/A',
                    'patient_id' => $incident->patient_id,
                    'nurse_name' => $incident->staff_family_role === 'nurse' ? $incident->staff_family_involved : 'N/A',
                    'staff_involved' => $incident->involved_staff,
                    'reporter_name' => $incident->reporter->first_name . ' ' . $incident->reporter->last_name,
                    'reported_at' => $incident->reported_at->format('Y-m-d H:i'),
                    'reviewer_name' => $incident->reviewer ? $incident->reviewer->first_name . ' ' . $incident->reviewer->last_name : null,
                    'reviewed_at' => $incident->reviewed_at?->format('Y-m-d H:i'),
                    'assigned_to' => $incident->assignedTo ? [
                        'id' => $incident->assignedTo->id,
                        'name' => $incident->assignedTo->first_name . ' ' . $incident->assignedTo->last_name,
                        'role' => $incident->assignedTo->role
                    ] : null,
                    'actions_taken' => $incident->corrective_preventive_actions,
                    'follow_up_required' => $incident->follow_up_required,
                    'follow_up_date' => $incident->follow_up_date?->format('Y-m-d'),
                    'is_overdue' => $incident->is_overdue,
                    'is_critical' => $incident->is_critical,
                    'first_aid_provided' => $incident->first_aid_provided,
                    'transferred_to_hospital' => $incident->transferred_to_hospital,
                    'days_old' => $incident->days_old,
                    'has_witnesses' => $incident->has_witnesses
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $incidents
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch incident reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new incident report
     */
    public function addIncidentReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
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
                'assigned_to' => 'nullable|exists:users,id'
            ]);

            // Validate patient exists and is actually a patient
            $patient = User::where('id', $request->patient_id)
                          ->where('role', 'patient')
                          ->first();
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient selected'
                ], 422);
            }
            
            // Validate assigned user if provided
            if ($request->assigned_to) {
                $assignedUser = User::findOrFail($request->assigned_to);
                if (!in_array($assignedUser->role, ['nurse', 'doctor', 'admin', 'superadmin'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User cannot be assigned to incident follow-up'
                    ], 422);
                }
            }

            // Prepare incident data
            $incidentData = [
                // Section 1: General Information
                'report_date' => $request->report_date,
                'incident_date' => $request->incident_date,
                'incident_time' => $request->incident_time,
                'incident_location' => $request->incident_location,
                'incident_type' => $request->incident_type,
                'incident_type_other' => $request->incident_type_other,
                
                // Section 2: Person(s) Involved
                'patient_id' => $request->patient_id,
                'patient_age' => $request->patient_age ?? $patient->age ?? null,
                'patient_sex' => $request->patient_sex ?? strtoupper(substr($patient->gender ?? 'M', 0, 1)),
                'client_id_case_no' => $request->client_id_case_no,
                'staff_family_involved' => $request->staff_family_involved,
                'staff_family_role' => $request->staff_family_role,
                'staff_family_role_other' => $request->staff_family_role_other,
                
                // Section 3: Description
                'incident_description' => $request->incident_description,
                
                // Section 4: Immediate Actions
                'first_aid_provided' => $request->first_aid_provided ?? false,
                'first_aid_description' => $request->first_aid_description,
                'care_provider_name' => $request->care_provider_name,
                'transferred_to_hospital' => $request->transferred_to_hospital ?? false,
                'hospital_transfer_details' => $request->hospital_transfer_details,
                
                // Section 5: Witnesses
                'witness_names' => $request->witness_names,
                'witness_contacts' => $request->witness_contacts,
                
                // Section 6: Follow-Up
                'reported_to_supervisor' => $request->reported_to_supervisor,
                'corrective_preventive_actions' => $request->corrective_preventive_actions,
                
                // Section 7: Reporting
                'reported_by' => auth()->id(),
                'reported_at' => now(),
                
                // Additional tracking
                'status' => 'pending',
                'severity' => $request->severity ?? 'medium',
                'follow_up_required' => $request->follow_up_required ?? false,
                'follow_up_date' => $request->follow_up_date,
                'assigned_to' => $request->assigned_to,
            ];

            // Create the incident report
            $incident = IncidentReport::create($incidentData);
            
            // Load fresh incident with relationships for response
            $newIncident = IncidentReport::with(['reporter', 'patient', 'assignedTo'])
                ->find($incident->id);
            
            // Prepare response data
            $responseData = [
                'id' => $newIncident->id,
                'report_date' => $newIncident->report_date->format('Y-m-d'),
                'incident_date' => $newIncident->incident_date->format('Y-m-d'),
                'incident_time' => $newIncident->incident_time,
                'incident_type' => $newIncident->incident_type,
                'incident_description' => $newIncident->incident_description,
                'patient_name' => $newIncident->patient->first_name . ' ' . $newIncident->patient->last_name,
                'patient_id' => $newIncident->patient_id,
                'reporter_name' => $newIncident->reporter->first_name . ' ' . $newIncident->reporter->last_name,
                'reported_at' => $newIncident->reported_at->format('Y-m-d H:i'),
                'status' => $newIncident->status,
                'severity' => $newIncident->severity,
                'follow_up_required' => $newIncident->follow_up_required,
                'follow_up_date' => $newIncident->follow_up_date?->format('Y-m-d'),
                'assigned_to' => $newIncident->assignedTo ? [
                    'id' => $newIncident->assignedTo->id,
                    'name' => $newIncident->assignedTo->first_name . ' ' . $newIncident->assignedTo->last_name,
                    'role' => $newIncident->assignedTo->role
                ] : null,
                'first_aid_provided' => $newIncident->first_aid_provided,
                'transferred_to_hospital' => $newIncident->transferred_to_hospital,
                'corrective_preventive_actions' => $newIncident->corrective_preventive_actions
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Incident report created successfully',
                'data' => $responseData
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create incident report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing incident report
     */
    public function updateIncidentReport(Request $request, $incidentId): JsonResponse
    {
        try {
            $incident = IncidentReport::findOrFail($incidentId);
            
            // CRITICAL: Check if incident is closed - prevent any edits
            if ($incident->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit closed incidents. Closed incidents are locked for audit purposes.',
                    'error_code' => 'INCIDENT_CLOSED'
                ], 422);
            }
            
            // Check permissions - only allow the reporter, assigned person, or admins to edit
            $user = auth()->user();
            if (!in_array($user->role, ['admin', 'superadmin']) && 
                $incident->reported_by !== $user->id && 
                $incident->assigned_to !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit this incident report'
                ], 403);
            }

            $request->validate([
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
                'assigned_to' => 'nullable|exists:users,id'
            ]);

            // Validate patient exists and is actually a patient
            $patient = User::where('id', $request->patient_id)
                        ->where('role', 'patient')
                        ->first();
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient selected'
                ], 422);
            }

            // Update incident data
            $updateData = [
                // Section 1: General Information
                'report_date' => $request->report_date,
                'incident_date' => $request->incident_date,
                'incident_time' => $request->incident_time,
                'incident_location' => $request->incident_location,
                'incident_type' => $request->incident_type,
                'incident_type_other' => $request->incident_type_other,
                
                // Section 2: Person(s) Involved
                'patient_id' => $request->patient_id,
                'patient_age' => $request->patient_age,
                'patient_sex' => $request->patient_sex,
                'client_id_case_no' => $request->client_id_case_no,
                'staff_family_involved' => $request->staff_family_involved,
                'staff_family_role' => $request->staff_family_role,
                'staff_family_role_other' => $request->staff_family_role_other,
                
                // Section 3: Description
                'incident_description' => $request->incident_description,
                
                // Section 4: Immediate Actions
                'first_aid_provided' => $request->first_aid_provided ?? false,
                'first_aid_description' => $request->first_aid_description,
                'care_provider_name' => $request->care_provider_name,
                'transferred_to_hospital' => $request->transferred_to_hospital ?? false,
                'hospital_transfer_details' => $request->hospital_transfer_details,
                
                // Section 5: Witnesses
                'witness_names' => $request->witness_names,
                'witness_contacts' => $request->witness_contacts,
                
                // Section 6: Follow-Up
                'reported_to_supervisor' => $request->reported_to_supervisor,
                'corrective_preventive_actions' => $request->corrective_preventive_actions,
                
                // Additional tracking
                'severity' => $request->severity ?? $incident->severity,
                'follow_up_required' => $request->follow_up_required ?? false,
                'follow_up_date' => $request->follow_up_date,
                'assigned_to' => $request->assigned_to,
                
                // Update timestamp and audit info
                'updated_at' => now(),
                'last_updated_by' => $user->id
            ];

            $incident->update($updateData);
            
            // Log the update for audit trail
            \Log::info('Incident report updated', [
                'incident_id' => $incident->id,
                'updated_by' => $user->id,
                'updated_by_name' => $user->first_name . ' ' . $user->last_name,
                'timestamp' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Incident report updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update incident report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an incident report
     */
    public function deleteIncidentReport($incidentId): JsonResponse
    {
        try {
            $incident = IncidentReport::findOrFail($incidentId);
            
            // CRITICAL: Check if incident is closed - prevent deletion
            if ($incident->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete closed incidents. Closed incidents are locked for audit purposes.',
                    'error_code' => 'INCIDENT_CLOSED'
                ], 422);
            }
            
            // Check permissions - only allow admins and superadmins to delete
            $user = auth()->user();
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete incident reports'
                ], 403);
            }

            // Prevent deletion of resolved incidents unless user is superadmin
            if ($incident->status === 'resolved' && $user->role !== 'superadmin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete resolved incident reports. Only super administrators can delete resolved incidents.',
                    'error_code' => 'INCIDENT_RESOLVED'
                ], 422);
            }

            // Log the deletion attempt for audit trail
            \Log::info('Incident report deleted', [
                'incident_id' => $incident->id,
                'incident_type' => $incident->incident_type,
                'patient_id' => $incident->patient_id,
                'deleted_by' => $user->id,
                'deleted_by_name' => $user->first_name . ' ' . $user->last_name,
                'timestamp' => now()
            ]);

            $incident->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Incident report deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete incident report',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get single incident report for editing
     */
    public function getIncidentReport($incidentId): JsonResponse
    {
        try {
            $incident = IncidentReport::with(['reporter', 'patient', 'assignedTo'])
                ->findOrFail($incidentId);
            
            // Check permissions
            $user = auth()->user();
            if (!in_array($user->role, ['admin', 'superadmin']) && 
                $incident->reported_by !== $user->id && 
                $incident->assigned_to !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this incident report'
                ], 403);
            }

            // IMPROVED time formatting function
            $formatTime = function($time) {
                if (!$time) return null;
                
                try {
                    // Log the original time value for debugging
                    \Log::info('Original time value: ' . $time . ' (type: ' . gettype($time) . ')');
                    
                    // If it's already in HH:MM format, return as-is
                    if (is_string($time) && preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                        // Ensure it's properly padded
                        $parts = explode(':', $time);
                        $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                        $minutes = $parts[1];
                        $formattedTime = $hours . ':' . $minutes;
                        \Log::info('Formatted time (HH:MM): ' . $formattedTime);
                        return $formattedTime;
                    }
                    
                    // If it's in HH:MM:SS format, remove seconds
                    if (is_string($time) && preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
                        $timePart = substr($time, 0, 5);
                        $parts = explode(':', $timePart);
                        $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                        $minutes = $parts[1];
                        $formattedTime = $hours . ':' . $minutes;
                        \Log::info('Formatted time (HH:MM:SS -> HH:MM): ' . $formattedTime);
                        return $formattedTime;
                    }
                    
                    // Try to parse with Carbon and format
                    $carbon = \Carbon\Carbon::parse($time);
                    $formattedTime = $carbon->format('H:i');
                    \Log::info('Formatted time (Carbon): ' . $formattedTime);
                    return $formattedTime;
                    
                } catch (\Exception $e) {
                    \Log::warning("Could not parse time value: " . $time . " - Error: " . $e->getMessage());
                    return null;
                }
            };

            $responseData = [
                'id' => $incident->id,
                
                // Section 1: General Information - with safe formatting
                'report_date' => $incident->report_date ? $incident->report_date->format('Y-m-d') : null,
                'incident_date' => $incident->incident_date ? $incident->incident_date->format('Y-m-d') : null,
                'incident_time' => $formatTime($incident->incident_time), // This should now work
                'incident_location' => $incident->incident_location ?? '',
                'incident_type' => $incident->incident_type ?? '',
                'incident_type_other' => $incident->incident_type_other ?? '',
                
                // Section 2: Person(s) Involved
                'patient_id' => $incident->patient_id,
                'patient_age' => $incident->patient_age ?? null,
                'patient_sex' => $incident->patient_sex ?? '',
                'client_id_case_no' => $incident->client_id_case_no ?? '',
                'staff_family_involved' => $incident->staff_family_involved ?? '',
                'staff_family_role' => $incident->staff_family_role ?? '',
                'staff_family_role_other' => $incident->staff_family_role_other ?? '',
                
                // Section 3: Description
                'incident_description' => $incident->incident_description ?? '',
                
                // Section 4: Immediate Actions - with proper boolean casting
                'first_aid_provided' => (bool)$incident->first_aid_provided,
                'first_aid_description' => $incident->first_aid_description ?? '',
                'care_provider_name' => $incident->care_provider_name ?? '',
                'transferred_to_hospital' => (bool)$incident->transferred_to_hospital,
                'hospital_transfer_details' => $incident->hospital_transfer_details ?? '',
                
                // Section 5: Witness Information
                'witness_names' => $incident->witness_names ?? '',
                'witness_contacts' => $incident->witness_contacts ?? '',
                
                // Section 6: Follow-Up Actions
                'reported_to_supervisor' => $incident->reported_to_supervisor ?? '',
                'corrective_preventive_actions' => $incident->corrective_preventive_actions ?? '',
                
                // Additional tracking
                'severity' => $incident->severity ?? 'medium',
                'follow_up_required' => (bool)$incident->follow_up_required,
                'follow_up_date' => $incident->follow_up_date ? $incident->follow_up_date->format('Y-m-d') : null,
                'assigned_to' => $incident->assigned_to ?? null,
                'status' => $incident->status ?? 'pending',
                
                // Meta information
                'created_at' => $incident->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $incident->updated_at->format('Y-m-d H:i:s'),
            ];
            
            // Log the final response for debugging
            \Log::info('Final incident_time in response: ' . ($responseData['incident_time'] ?? 'null'));
            
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Incident report not found'
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching incident report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch incident report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get quality metrics data
     */
    public function getQualityMetrics(Request $request): JsonResponse
    {
        try {
            $timeframe = $request->get('timeframe', '30');
            $dateFrom = Carbon::now()->subDays($timeframe);
            $dateTo = Carbon::now();

            $metrics = [
                'patient_satisfaction' => $this->getPatientSatisfactionMetrics($dateFrom, $dateTo),
                'care_quality' => $this->getCareQualityMetrics($dateFrom, $dateTo),
                'operational_efficiency' => $this->getOperationalEfficiencyMetrics($dateFrom, $dateTo),
                'safety_metrics' => $this->getSafetyMetrics($dateFrom, $dateTo),
                'compliance_metrics' => $this->getComplianceMetrics($dateFrom, $dateTo),
                'trends' => $this->getQualityTrends($dateFrom, $dateTo)
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quality metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update incident report status
     */
    public function updateIncidentStatus(Request $request, $incidentId): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,under_review,investigated,resolved,closed',
                'actions_taken' => 'nullable|string|max:1000',
                'follow_up_required' => 'boolean',
                'follow_up_date' => 'nullable|date|after:today',
                // 'closure_reason' => 'required_if:status,closed|nullable|string|max:500'
            ]);

            $incident = IncidentReport::findOrFail($incidentId);
            $user = auth()->user();
            
            // CRITICAL: Once closed, status cannot be changed
            if ($incident->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify closed incidents. Closed incidents are immutable for audit purposes.',
                    'error_code' => 'INCIDENT_CLOSED'
                ], 422);
            }
            
            // Check permissions
            if (!in_array($user->role, ['admin', 'superadmin']) && 
                $incident->assigned_to !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this incident status'
                ], 403);
            }

            // Business rule: Only admins/superadmins can close incidents
            if ($request->status === 'closed' && !in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can close incident reports'
                ], 403);
            }

            // Business rule: Cannot skip from pending directly to closed
            if ($incident->status === 'pending' && $request->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Incidents must be investigated before being closed'
                ], 422);
            }

            $updateData = [
                'status' => $request->status,
                'follow_up_required' => $request->follow_up_required ?? false,
                'follow_up_date' => $request->follow_up_date,
                'last_updated_by' => $user->id
            ];

            // Add actions taken if provided
            if ($request->actions_taken) {
                $updateData['corrective_preventive_actions'] = $request->actions_taken;
            }

            // Handle status-specific updates
            switch ($request->status) {
                case 'under_review':
                case 'investigated':
                    if (!$incident->reviewed_at) {
                        $updateData['reviewed_by'] = $user->id;
                        $updateData['reviewed_at'] = now();
                    }
                    break;
                    
                case 'resolved':
                    $updateData['resolved_by'] = $user->id;
                    $updateData['resolved_at'] = now();
                    break;
                    
                case 'closed':
                    $updateData['closed_by'] = $user->id;
                    $updateData['closed_at'] = now();
                    $updateData['closure_reason'] = $request->closure_reason;
                    $updateData['follow_up_required'] = false; // No follow-up needed for closed incidents
                    $updateData['follow_up_date'] = null;
                    break;
            }

            $incident->update($updateData);

            // Log the status change for audit trail
            \Log::info('Incident status updated', [
                'incident_id' => $incident->id,
                'old_status' => $incident->getOriginal('status'),
                'new_status' => $request->status,
                'updated_by' => $user->id,
                'updated_by_name' => $user->first_name . ' ' . $user->last_name,
                'timestamp' => now(),
                'actions_taken' => $request->actions_taken
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Incident status updated successfully',
                'data' => [
                    'status' => $incident->status,
                    'is_closed' => $incident->status === 'closed'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update incident status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // HELPER METHODS FOR DATA AGGREGATION
    // ============================================

    private function getQualityOverview($dateFrom)
    {
        return [
            'total_feedback' => PatientFeedback::where('created_at', '>=', $dateFrom)->count(),
            'avg_satisfaction' => round(PatientFeedback::where('created_at', '>=', $dateFrom)->avg('rating') ?? 0, 1),
            'total_incidents' => IncidentReport::where('created_at', '>=', $dateFrom)->count(),
            'critical_incidents' => IncidentReport::where('created_at', '>=', $dateFrom)->where('severity', 'critical')->count(),
            'active_nurses' => User::where('role', 'nurse')->where('is_active', true)->count(),
            'active_care_plans' => CarePlan::where('status', 'active')->count(),
            'total_patients' => User::where('role', 'patient')->where('is_active', true)->count(),
            'care_sessions' => TimeTracking::where('created_at', '>=', $dateFrom)
                                            ->where('status', 'completed')
                                            ->whereNotNull('patient_id')
                                            ->count(),
        ];
    }

    private function getPatientFeedbackSummary($dateFrom)
    {
        $totalFeedback = PatientFeedback::where('created_at', '>=', $dateFrom)->count();
        $avgRating = PatientFeedback::where('created_at', '>=', $dateFrom)->avg('rating') ?? 0;

        return [
            'total_feedback' => $totalFeedback,
            'avg_rating' => round($avgRating, 1),
            'feedback_distribution' => $this->getFeedbackDistribution($dateFrom)
        ];
    }

    private function getFeedbackDistribution($dateFrom)
    {
        return [
            '5_star' => PatientFeedback::where('created_at', '>=', $dateFrom)->where('rating', 5)->count(),
            '4_star' => PatientFeedback::where('created_at', '>=', $dateFrom)->where('rating', 4)->count(),
            '3_star' => PatientFeedback::where('created_at', '>=', $dateFrom)->where('rating', 3)->count(),
            '2_star' => PatientFeedback::where('created_at', '>=', $dateFrom)->where('rating', 2)->count(),
            '1_star' => PatientFeedback::where('created_at', '>=', $dateFrom)->where('rating', 1)->count(),
        ];
    }

    private function getNursePerformanceSummary($dateFrom)
    {
        $nurses = User::where('role', 'nurse')->where('is_active', true);
        $totalNurses = $nurses->count();
        
        if ($totalNurses === 0) {
            return [
                'total_nurses' => 0,
                'avg_performance_score' => 0,
                'top_performer' => null,
                'nurses_needing_attention' => 0
            ];
        }

        // Calculate performance scores
        $nurseData = $nurses->get()->map(function ($nurse) use ($dateFrom) {
            // Get assigned care plans directly from CarePlan model
            $activeCarePlans = CarePlan::where(function($query) use ($nurse) {
                $query->where('primary_nurse_id', $nurse->id)
                      ->orWhere('secondary_nurse_id', $nurse->id);
            })->where('status', 'active')->count();
            
            $totalHours = TimeTracking::where('nurse_id', $nurse->id)
                ->where('created_at', '>=', $dateFrom)
                ->where('status', 'completed')
                ->sum('total_duration_minutes') / 60 ?? 0;
            
            // FIX: Define $careSessionsCount variable here
            $careSessionsCount = TimeTracking::where('nurse_id', $nurse->id)
                ->where('created_at', '>=', $dateFrom)
                ->where('status', 'completed')
                ->whereNotNull('patient_id')
                ->count();
            
            $feedback = $nurse->feedback()->where('created_at', '>=', $dateFrom);
            $avgRating = $feedback->avg('rating') ?? 0;
            $feedbackCount = $feedback->count();
            
            $incidents = $nurse->incidentReports()->where('created_at', '>=', $dateFrom)->count();
            $punctualityScore = $this->calculatePunctualityScore($nurse, $dateFrom);
            
            // Calculate overall score
            $overallScore = $this->calculateOverallScore($avgRating, $punctualityScore, $incidents);

            return [
                'nurse' => $nurse,
                'score' => $overallScore,
                'data' => [
                    'id' => $nurse->id,
                    'name' => $nurse->first_name . ' ' . $nurse->last_name,
                    'avatar_url' => $nurse->avatar_url,
                    'license_number' => $nurse->license_number,
                    'specialization' => $nurse->specialization,
                    'active_care_plans' => $activeCarePlans,
                    'care_sessions' => $careSessionsCount,
                    'total_hours' => round($totalHours, 1),
                    'avg_rating' => round($avgRating, 1),
                    'feedback_count' => $feedbackCount,
                    'incident_count' => $incidents,
                    'punctuality_score' => round($punctualityScore, 1),
                    'overall_score' => round($overallScore, 1),
                    'performance_grade' => $this->getPerformanceGrade($overallScore),
                    'last_activity' => TimeTracking::where('nurse_id', $nurse->id)->latest()->first()?->created_at?->format('Y-m-d') ?? 'No recent activity',
                ]
            ];
        });

        $avgScore = $nurseData->avg('score');
        $topPerformer = $nurseData->sortByDesc('score')->first();

        return [
            'total_nurses' => $totalNurses,
            'avg_performance_score' => round($avgScore, 1),
            'top_performer' => $topPerformer ? $topPerformer['nurse']->first_name . ' ' . $topPerformer['nurse']->last_name : null,
            'nurses_needing_attention' => $nurseData->filter(fn($data) => $data['score'] < 70)->count()
        ];
    }

    private function getIncidentReportsSummary($dateFrom)
    {
        $incidents = IncidentReport::where('created_at', '>=', $dateFrom);
        
        return [
            'total_incidents' => $incidents->count(),
            'critical_incidents' => $incidents->where('severity', 'critical')->count(),
            'pending_incidents' => $incidents->where('status', 'pending')->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($dateFrom, Carbon::now())
        ];
    }

    private function getQualityMetricsSummary($dateFrom)
    {
        $patientSatisfaction = PatientFeedback::where('created_at', '>=', $dateFrom)->avg('rating') ?? 0;
        $incidentRate = $this->calculateIncidentRate($dateFrom);
        
        return [
            'overall_quality_score' => round(($patientSatisfaction / 5) * 100, 1),
            'patient_satisfaction' => round($patientSatisfaction, 1),
            'care_compliance' => $this->calculateCareCompliance($dateFrom),
            'incident_rate' => round($incidentRate, 2),
            'safety_score' => round(max(0, 100 - ($incidentRate * 10)), 1)
        ];
    }

    private function getPatientSatisfactionMetrics($dateFrom, $dateTo)
    {
        $satisfaction = PatientFeedback::whereBetween('created_at', [$dateFrom, $dateTo]);
        
        return [
            'average_rating' => round($satisfaction->avg('rating') ?? 0, 1),
            'total_responses' => $satisfaction->count(),
            'rating_distribution' => [
                '5_star' => $satisfaction->clone()->where('rating', 5)->count(),
                '4_star' => $satisfaction->clone()->where('rating', 4)->count(),
                '3_star' => $satisfaction->clone()->where('rating', 3)->count(),
                '2_star' => $satisfaction->clone()->where('rating', 2)->count(),
                '1_star' => $satisfaction->clone()->where('rating', 1)->count(),
            ],
            'feedback_response_rate' => $this->calculateFeedbackResponseRate($dateFrom, $dateTo),
            'recommendation_rate' => round($satisfaction->where('would_recommend', true)->count() / max($satisfaction->count(), 1) * 100, 1)
        ];
    }

    private function getCareQualityMetrics($dateFrom, $dateTo)
    {
        return [
            'care_plan_completion_rate' => $this->calculateCarePlanCompletionRate($dateFrom, $dateTo),
            'documentation_completeness' => $this->calculateDocumentationCompleteness($dateFrom, $dateTo),
            'protocol_adherence' => $this->calculateProtocolAdherence($dateFrom, $dateTo),
            'care_plan_adherence' => $this->calculateCarePlanAdherence($dateFrom, $dateTo),
            'vitals_monitoring_consistency' => $this->calculateVitalsMonitoringConsistency($dateFrom, $dateTo),
            'medication_compliance' => $this->calculateMedicationCompliance($dateFrom, $dateTo),
        ];
    }

    private function getOperationalEfficiencyMetrics($dateFrom, $dateTo)
    {
        return [
            'avg_care_hours_per_plan' => $this->calculateAverageHoursPerCarePlan($dateFrom, $dateTo),
            'schedule_adherence' => $this->calculateScheduleAdherence($dateFrom, $dateTo),
            'nurse_utilization' => $this->calculateNurseUtilization($dateFrom, $dateTo),
            'care_plan_efficiency' => $this->calculateCarePlanEfficiency($dateFrom, $dateTo),
        ];
    }

    private function getSafetyMetrics($dateFrom, $dateTo)
    {
        $totalIncidents = IncidentReport::whereBetween('created_at', [$dateFrom, $dateTo]);
        
        return [
            'total_incidents' => $totalIncidents->count(),
            'incidents_by_severity' => [
                'critical' => $totalIncidents->clone()->where('severity', 'critical')->count(),
                'high' => $totalIncidents->clone()->where('severity', 'high')->count(),
                'medium' => $totalIncidents->clone()->where('severity', 'medium')->count(),
                'low' => $totalIncidents->clone()->where('severity', 'low')->count(),
            ],
            'incidents_by_category' => $this->getIncidentsByType($dateFrom, $dateTo),
            'resolution_time_avg' => $this->calculateAverageResolutionTime($dateFrom, $dateTo),
            'incident_rate' => $this->calculateIncidentRate($dateFrom),
        ];
    }


    private function calculateMedicationCompliance($dateFrom, $dateTo)
    {
        try {
            // Get assessments with medication information
            $assessmentsWithMeds = MedicalAssessment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('current_medications')
                ->where('current_medications', '!=', '')
                ->get();

            if ($assessmentsWithMeds->count() === 0) {
                return 100; // No medication data to evaluate
            }

            // For now, assume good compliance if medications are documented
            // In a real system, you'd track actual medication adherence
            $patientsWithMedications = $assessmentsWithMeds->count();
            $totalPatients = MedicalAssessment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->distinct('patient_id')
                ->count();

            if ($totalPatients === 0) {
                return 100;
            }

            // Calculate compliance as percentage of patients with documented medications
            $compliance = ($patientsWithMedications / $totalPatients) * 100;
            return round(min($compliance, 100), 1); // Cap at 100%

        } catch (\Exception $e) {
            \Log::error('Error calculating medication compliance: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate vitals monitoring consistency based on medical assessments and care sessions
     */
    private function calculateVitalsMonitoringConsistency($dateFrom, $dateTo)
    {
        try {
            // Get all medical assessments within the timeframe
            $assessments = MedicalAssessment::whereBetween('created_at', [$dateFrom, $dateTo])->get();
            
            if ($assessments->count() === 0) {
                return 100; // No assessments to evaluate
            }

            $completeVitalsCount = 0;
            $totalAssessments = $assessments->count();

            foreach ($assessments as $assessment) {
                $vitals = $assessment->initial_vitals ?? [];
                
                // Define required vital signs that should be monitored
                $requiredVitals = ['temperature', 'pulse', 'blood_pressure', 'spo2'];
                $recordedVitals = 0;

                foreach ($requiredVitals as $vital) {
                    if (!empty($vitals[$vital]) && $vitals[$vital] !== null && $vitals[$vital] !== '') {
                        $recordedVitals++;
                    }
                }

                // Consider vitals complete if at least 75% of required vitals are recorded
                $completionThreshold = count($requiredVitals) * 0.75; // 75% threshold
                if ($recordedVitals >= $completionThreshold) {
                    $completeVitalsCount++;
                }
            }

            $consistency = ($completeVitalsCount / $totalAssessments) * 100;
            return round($consistency, 1);

        } catch (\Exception $e) {
            \Log::error('Error calculating vitals monitoring consistency: ' . $e->getMessage());
            return 0;
        }
    }


    private function getComplianceMetrics($dateFrom, $dateTo)
    {
        return [
            'documentation_compliance' => $this->calculateDocumentationCompliance($dateFrom, $dateTo),
            'care_plan_compliance' => $this->calculateCarePlanCompliance($dateFrom, $dateTo),
            'training_compliance' => $this->calculateTrainingCompliance($dateFrom, $dateTo),
            'certification_status' => $this->getCertificationStatus(),
        ];
    }

    private function getQualityTrends($dateFrom, $dateTo)
    {
        $days = $dateFrom->diffInDays($dateTo);
        $interval = $days > 30 ? 'week' : 'day';
        
        return [
            'satisfaction_trend' => $this->getSatisfactionTrend($dateFrom, $dateTo, $interval),
            'incident_trend' => $this->getIncidentTrend($dateFrom, $dateTo, $interval),
            'performance_trend' => $this->getPerformanceTrend($dateFrom, $dateTo, $interval),
        ];
    }

    // ============================================
    // CALCULATION HELPER METHODS
    // ============================================

    private function calculatePunctualityScore($nurse, $dateFrom)
    {
        $timeTrackings = TimeTracking::where('nurse_id', $nurse->id)
            ->where('created_at', '>=', $dateFrom)
            ->with('schedule')
            ->get();
        
        if ($timeTrackings->count() === 0) return 100;

        $onTimeCount = $timeTrackings->filter(function($tracking) {
            if (!$tracking->schedule || !$tracking->start_time) return true;
            
            try {
                // Get the scheduled date
                $scheduleDate = Carbon::parse($tracking->schedule->schedule_date)->format('Y-m-d');
                
                // Extract just the time component from start_time
                $startTimeValue = $tracking->schedule->start_time;
                
                // Handle different formats of start_time
                if (strpos($startTimeValue, ' ') !== false) {
                    // If start_time contains a date part, extract just the time
                    $timeOnly = Carbon::parse($startTimeValue)->format('H:i:s');
                } else {
                    // If it's already just time, use as is
                    $timeOnly = $startTimeValue;
                }
                
                // Combine the schedule date with the extracted time
                $scheduledStart = Carbon::parse($scheduleDate . ' ' . $timeOnly);
                $actualStart = Carbon::parse($tracking->start_time);
                
                // Calculate difference in minutes (negative if early, positive if late)
                $minutesDifference = $actualStart->diffInMinutes($scheduledStart, false);
                
                // Consider on-time if within 15 minutes (early or late)
                return abs($minutesDifference) <= 15;
                
            } catch (\Exception $e) {
                \Log::error("Error parsing punctuality dates for tracking {$tracking->id}: " . $e->getMessage(), [
                    'schedule_date' => $tracking->schedule->schedule_date ?? 'null',
                    'start_time' => $tracking->schedule->start_time ?? 'null',
                    'actual_start_time' => $tracking->start_time ?? 'null'
                ]);
                
                // If parsing fails, consider it on-time to avoid penalizing
                return true;
            }
        })->count();
        
        $punctualityScore = ($onTimeCount / $timeTrackings->count()) * 100;
        
        return $punctualityScore;
    }
    
    private function calculateOverallScore($avgRating, $punctualityScore, $incidentCount)
    {
        $ratingScore = ($avgRating / 5) * 40; // 40% weight
        $punctualityWeight = ($punctualityScore / 100) * 35; // 35% weight
        $safetyScore = max(0, 25 - ($incidentCount * 5)); // 25% weight, penalize incidents
        
        return $ratingScore + $punctualityWeight + $safetyScore;
    }

    private function getPerformanceGrade($score)
    {
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 30) return 'D';
        return 'F';
    }

    private function calculateFeedbackResponseRate($dateFrom, $dateTo)
    {
        // Using care plans instead of care sessions
        $totalCarePlans = CarePlan::whereBetween('created_at', [$dateFrom, $dateTo])
                                 ->where('status', 'completed')
                                 ->count();
        $feedbackReceived = PatientFeedback::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        
        return $totalCarePlans > 0 ? round(($feedbackReceived / $totalCarePlans) * 100, 1) : 0;
    }

    private function calculateCarePlanCompletionRate($dateFrom, $dateTo)
    {
        $totalPlans = CarePlan::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $completedPlans = CarePlan::whereBetween('updated_at', [$dateFrom, $dateTo])
                                 ->where('status', 'completed')
                                 ->count();
        
        return $totalPlans > 0 ? round(($completedPlans / $totalPlans) * 100, 1) : 0;
    }

    private function calculateCarePlanAdherence($dateFrom, $dateTo)
    {
        // Calculate based on completion percentage in care plans
        $carePlans = CarePlan::whereBetween('updated_at', [$dateFrom, $dateTo])
                            ->where('status', 'active')
                            ->get();

        if ($carePlans->count() === 0) return 100;

        $avgCompletion = $carePlans->avg('completion_percentage') ?? 0;
        return round($avgCompletion, 1);
    }

    private function calculateDocumentationCompleteness($dateFrom, $dateTo)
    {
        // Check if care plans have proper documentation
        $carePlans = CarePlan::whereBetween('created_at', [$dateFrom, $dateTo])->get();
        
        if ($carePlans->count() === 0) return 100;

        $completeDocumentation = $carePlans->filter(function ($plan) {
            return !empty($plan->description) && 
                   !empty($plan->care_tasks) && 
                   !empty($plan->care_type);
        })->count();

        return round(($completeDocumentation / $carePlans->count()) * 100, 1);
    }

    private function calculateAverageHoursPerCarePlan($dateFrom, $dateTo)
    {
        // Calculate average hours worked per care plan using TimeTracking
        $hoursWorked = TimeTracking::whereBetween('created_at', [$dateFrom, $dateTo])
                              ->where('status', 'completed')
                              ->sum('total_duration_minutes') / 60;
        
        $activeCarePlans = CarePlan::where('status', 'active')->count();
        
        return $activeCarePlans > 0 ? round($hoursWorked / $activeCarePlans, 1) : 0;
    }

    private function calculateScheduleAdherence($dateFrom, $dateTo)
    {
        $timeTrackings = TimeTracking::whereBetween('created_at', [$dateFrom, $dateTo])
                                    ->with('schedule')
                                    ->get();
        
        if ($timeTrackings->count() === 0) return 100;

        // Calculate punctuality based on scheduled vs actual start times
        $onTimeCount = $timeTrackings->filter(function($tracking) {
            if (!$tracking->schedule || !$tracking->start_time) return true;
            
            $scheduledStart = Carbon::parse($tracking->schedule->start_time);
            $actualStart = Carbon::parse($tracking->start_time);
            
            // Consider on-time if within 15 minutes of scheduled time
            return $actualStart->diffInMinutes($scheduledStart, false) <= 15;
        })->count();
        
        return round(($onTimeCount / $timeTrackings->count()) * 100, 1);
    }

    private function calculateNurseUtilization($dateFrom, $dateTo)
    {
        $totalNurses = User::where('role', 'nurse')->where('is_active', true)->count();
        $activeNurses = TimeTracking::whereBetween('created_at', [$dateFrom, $dateTo])
                               ->distinct('nurse_id')
                               ->count('nurse_id');
        
        return $totalNurses > 0 ? round(($activeNurses / $totalNurses) * 100, 1) : 0;
    }

    private function calculateCarePlanEfficiency($dateFrom, $dateTo)
    {
        // Calculate efficiency based on care plan completion time
        $completedPlans = CarePlan::whereBetween('updated_at', [$dateFrom, $dateTo])
                                 ->where('status', 'completed')
                                 ->whereNotNull('start_date')
                                 ->whereNotNull('end_date')
                                 ->get();

        if ($completedPlans->count() === 0) return 100;

        $avgEfficiency = $completedPlans->map(function ($plan) {
            $planned = Carbon::parse($plan->start_date)->diffInDays(Carbon::parse($plan->end_date));
            $actual = Carbon::parse($plan->start_date)->diffInDays($plan->updated_at);
            
            return $planned > 0 ? min(100, ($planned / max($actual, 1)) * 100) : 100;
        })->avg();

        return round($avgEfficiency, 1);
    }

    private function calculateIncidentRate($dateFrom)
    {
        $days = Carbon::now()->diffInDays($dateFrom);
        $incidents = IncidentReport::where('created_at', '>=', $dateFrom)->count();
        $activeCarePlans = CarePlan::where('status', 'active')->count();
        
        return $activeCarePlans > 0 ? ($incidents / $activeCarePlans) : 0;
    }

    private function calculateCareCompliance($dateFrom)
    {
        // Calculate compliance based on care plan adherence
        return $this->calculateCarePlanAdherence($dateFrom, Carbon::now());
    }

    private function getIncidentsByType($dateFrom, $dateTo)
    {
        return IncidentReport::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('incident_type', DB::raw('count(*) as count'))
            ->groupBy('incident_type')
            ->pluck('count', 'incident_type')
            ->toArray();
    }

    private function calculateAverageResolutionTime($dateFrom, $dateTo)
    {
        $resolvedIncidents = IncidentReport::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['resolved', 'closed'])
            ->get();

        if ($resolvedIncidents->count() === 0) return 0;

        $totalHours = $resolvedIncidents->sum(function ($incident) {
           
            return $incident->reported_at->diffInHours($incident->reviewed_at);
        });

        return round($totalHours / $resolvedIncidents->count(), 1);
    }


    private function calculateDocumentationCompliance($dateFrom, $dateTo)
    {
        // Check if time tracking sessions have proper documentation
        $timeTrackings = TimeTracking::whereBetween('created_at', [$dateFrom, $dateTo])->get();
        
        if ($timeTrackings->count() === 0) return 100;

        $properlyDocumented = $timeTrackings->filter(function ($tracking) {
            return !empty($tracking->work_notes) && 
                   $tracking->end_time !== null;
        })->count();

        return round(($properlyDocumented / $timeTrackings->count()) * 100, 1);
    }

    private function calculateProtocolAdherence($dateFrom, $dateTo)
    {
        // Calculate adherence based on care plan completion
        return $this->calculateCarePlanAdherence($dateFrom, $dateTo);
    }

    private function calculateCarePlanCompliance($dateFrom, $dateTo)
    {
        return $this->calculateCarePlanAdherence($dateFrom, $dateTo);
    }

    private function calculateTrainingCompliance($dateFrom, $dateTo)
    {
        // Calculate staff training compliance - placeholder implementation
        $activeNurses = User::where('role', 'nurse')->where('is_active', true)->count();
        $certifiedNurses = User::where('role', 'nurse')
                              ->where('is_active', true)
                              ->whereNotNull('license_number')
                              ->count();
        
        return $activeNurses > 0 ? round(($certifiedNurses / $activeNurses) * 100, 1) : 0;
    }

    private function getCertificationStatus()
    {
        $nurses = User::where('role', 'nurse')->where('is_active', true);
        $total = $nurses->count();
        $certified = $nurses->whereNotNull('license_number')->count();
        
        return [
            'total_nurses' => $total,
            'certified_nurses' => $certified,
            'certification_rate' => $total > 0 ? round(($certified / $total) * 100, 1) : 0
        ];
    }

    private function getSatisfactionTrend($dateFrom, $dateTo, $interval)
    {
        $format = $interval === 'week' ? '%Y-%u' : '%Y-%m-%d';
        
        return PatientFeedback::selectRaw("DATE_FORMAT(created_at, '{$format}') as period, AVG(rating) as avg_rating")
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->period,
                    'value' => round($item->avg_rating, 1)
                ];
            });
    }

    private function getIncidentTrend($dateFrom, $dateTo, $interval)
    {
        $format = $interval === 'week' ? '%Y-%u' : '%Y-%m-%d';
        
        return IncidentReport::selectRaw("DATE_FORMAT(created_at, '{$format}') as period, COUNT(*) as incident_count")
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->period,
                    'value' => $item->incident_count
                ];
            });
    }

    private function getPerformanceTrend($dateFrom, $dateTo, $interval)
    {
        $format = $interval === 'week' ? '%Y-%u' : '%Y-%m-%d';
        
        return PatientFeedback::selectRaw("DATE_FORMAT(created_at, '{$format}') as period, AVG(rating) as performance")
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->period,
                    'value' => round(($item->performance / 5) * 100, 1)
                ];
            });
    }


    /**
     * Respond to patient feedback
     */
    public function respondToFeedback(Request $request, $feedbackId): JsonResponse
    {
        try {
            $request->validate([
                'response_text' => 'required|string|max:1000'
            ]);

            $feedback = PatientFeedback::findOrFail($feedbackId);
            
            // Check permissions - only admins/superadmins can respond
            $user = auth()->user();
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to respond to feedback'
                ], 403);
            }

            $feedback->update([
                'admin_response' => $request->response_text,
                'response_date' => now(),
                'responded_by' => $user->id,
                'status' => 'responded'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Response sent successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send response',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export reports
     */
    public function exportReport($reportType): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check permissions
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to export reports'
                ], 403);
            }

            switch ($reportType) {
                case 'patient-feedback':
                    return $this->exportPatientFeedback();
                case 'nurse-performance':
                    return $this->exportNursePerformance();
                case 'incident-reports':
                    return $this->exportIncidentReports();
                case 'quality-metrics':
                    return $this->exportQualityMetrics();
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid report type'
                    ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export patient feedback as CSV
     */
    private function exportPatientFeedback()
    {
        $feedback = PatientFeedback::with(['patient', 'nurse'])
            ->latest()
            ->get();

        $csvData = [];
        $csvData[] = [
            'Date',
            'Patient Name',
            'Nurse Name',
            'Rating',
            'Feedback',
            'Category',
            'Would Recommend',
            'Status'
        ];

        foreach ($feedback as $item) {
            $csvData[] = [
                $item->created_at->format('Y-m-d H:i'),
                $item->patient->first_name . ' ' . $item->patient->last_name,
                $item->nurse->first_name . ' ' . $item->nurse->last_name,
                $item->rating,
                $item->feedback_text,
                $item->feedback_category ?? 'General',
                $item->would_recommend ? 'Yes' : 'No',
                $item->status ?? 'Pending'
            ];
        }

        return $this->generateCsvResponse($csvData, 'patient_feedback_' . date('Y-m-d') . '.csv');
    }

    /**
     * Export nurse performance as CSV
     */
    private function exportNursePerformance()
    {
        $timeframe = request()->get('timeframe', '30');
        $dateFrom = Carbon::now()->subDays($timeframe);

        $nurses = User::where('role', 'nurse')
            ->where('is_active', true)
            ->get();

        $csvData = [];
        $csvData[] = [
            'Nurse Name',
            'License Number',
            'Specialization',
            'Care Sessions',
            'Total Hours',
            'Average Rating',
            'Feedback Count',
            'Incident Count',
            'Punctuality Score',
            'Overall Score',
            'Performance Grade'
        ];

        foreach ($nurses as $nurse) {
            $careSessionsCount = TimeTracking::where('nurse_id', $nurse->id)
                ->where('created_at', '>=', $dateFrom)
                ->where('status', 'completed')
                ->whereNotNull('patient_id')
                ->count();
            
            $totalHours = TimeTracking::where('nurse_id', $nurse->id)
                ->where('created_at', '>=', $dateFrom)
                ->where('status', 'completed')
                ->sum('total_duration_minutes') / 60 ?? 0;
            
            $feedback = $nurse->feedback()->where('created_at', '>=', $dateFrom);
            $avgRating = $feedback->avg('rating') ?? 0;
            $feedbackCount = $feedback->count();
            
            $incidents = $nurse->incidentReports()->where('created_at', '>=', $dateFrom)->count();
            $punctualityScore = $this->calculatePunctualityScore($nurse, $dateFrom);
            $overallScore = $this->calculateOverallScore($avgRating, $punctualityScore, $incidents);

            $csvData[] = [
                $nurse->first_name . ' ' . $nurse->last_name,
                $nurse->license_number ?? 'N/A',
                $nurse->specialization ?? 'N/A',
                $careSessionsCount,
                round($totalHours, 1),
                round($avgRating, 1),
                $feedbackCount,
                $incidents,
                round($punctualityScore, 1),
                round($overallScore, 1),
                $this->getPerformanceGrade($overallScore)
            ];
        }

        return $this->generateCsvResponse($csvData, 'nurse_performance_' . date('Y-m-d') . '.csv');
    }

    /**
     * Export incident reports as CSV
     */
    private function exportIncidentReports()
    {
        $incidents = IncidentReport::with(['reporter', 'patient', 'assignedTo'])
            ->latest()
            ->get();

        $csvData = [];
        $csvData[] = [
            'Report Date',
            'Incident Date',
            'Incident Time',
            'Type',
            'Location',
            'Severity',
            'Status',
            'Patient Name',
            'Staff Involved',
            'Description',
            'First Aid Provided',
            'Hospital Transfer',
            'Reporter',
            'Assigned To'
        ];

        foreach ($incidents as $incident) {
            $csvData[] = [
                $incident->report_date->format('Y-m-d'),
                $incident->incident_date->format('Y-m-d'),
                $incident->incident_time ?? '',
                $incident->incident_type,
                $incident->incident_location ?? '',
                $incident->severity,
                $incident->status,
                $incident->patient ? $incident->patient->first_name . ' ' . $incident->patient->last_name : 'N/A',
                $incident->staff_family_involved ?? 'N/A',
                $incident->incident_description,
                $incident->first_aid_provided ? 'Yes' : 'No',
                $incident->transferred_to_hospital ? 'Yes' : 'No',
                $incident->reporter->first_name . ' ' . $incident->reporter->last_name,
                $incident->assignedTo ? $incident->assignedTo->first_name . ' ' . $incident->assignedTo->last_name : 'Unassigned'
            ];
        }

        return $this->generateCsvResponse($csvData, 'incident_reports_' . date('Y-m-d') . '.csv');
    }

    /**
     * Export quality metrics as CSV
     */
    private function exportQualityMetrics()
    {
        $timeframe = request()->get('timeframe', '30');
        $dateFrom = Carbon::now()->subDays($timeframe);
        $dateTo = Carbon::now();

        $metrics = [
            'patient_satisfaction' => $this->getPatientSatisfactionMetrics($dateFrom, $dateTo),
            'care_quality' => $this->getCareQualityMetrics($dateFrom, $dateTo),
            'operational_efficiency' => $this->getOperationalEfficiencyMetrics($dateFrom, $dateTo),
            'safety_metrics' => $this->getSafetyMetrics($dateFrom, $dateTo),
        ];

        $csvData = [];
        $csvData[] = ['Metric Category', 'Metric Name', 'Value', 'Unit'];

        // Patient Satisfaction
        $csvData[] = ['Patient Satisfaction', 'Average Rating', $metrics['patient_satisfaction']['average_rating'], '/5'];
        $csvData[] = ['Patient Satisfaction', 'Total Responses', $metrics['patient_satisfaction']['total_responses'], 'count'];
        $csvData[] = ['Patient Satisfaction', 'Response Rate', $metrics['patient_satisfaction']['response_rate'] ?? 0, '%'];

        // Care Quality
        $csvData[] = ['Care Quality', 'Care Plan Completion Rate', $metrics['care_quality']['care_plan_completion_rate'] ?? 0, '%'];
        $csvData[] = ['Care Quality', 'Documentation Completeness', $metrics['care_quality']['documentation_completeness'] ?? 0, '%'];

        // Operational Efficiency
        $csvData[] = ['Operational Efficiency', 'Schedule Adherence', $metrics['operational_efficiency']['schedule_adherence'] ?? 0, '%'];
        $csvData[] = ['Operational Efficiency', 'Nurse Utilization', $metrics['operational_efficiency']['nurse_utilization'] ?? 0, '%'];

        // Safety Metrics
        $csvData[] = ['Safety', 'Total Incidents', $metrics['safety_metrics']['total_incidents'], 'count'];
        $csvData[] = ['Safety', 'Critical Incidents', $metrics['safety_metrics']['incidents_by_severity']['critical'] ?? 0, 'count'];
        $csvData[] = ['Safety', 'Average Resolution Time', $metrics['safety_metrics']['resolution_time_avg'] ?? 0, 'hours'];

        return $this->generateCsvResponse($csvData, 'quality_metrics_' . date('Y-m-d') . '.csv');
    }

    /**
     * Generate CSV response
     */
    private function generateCsvResponse($data, $filename)
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}