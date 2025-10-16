<?php

namespace App\Http\Controllers;

use App\Models\ProgressNote;
use App\Models\User;
use App\Models\CarePlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressNoteController extends Controller
{
    /**
     * Display a listing of progress notes.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = ProgressNote::with(['patient', 'nurse'])->ordered();

            // Apply role-based filtering
            $this->applyRoleBasedFiltering($query, $user);

            // Apply additional filters
            if ($request->filled('patient_id')) {
                // Ensure user can access this patient's data
                if ($this->canAccessPatientData($user, $request->patient_id)) {
                    $query->forPatient($request->patient_id);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to access this patient\'s data.',
                    ], 403);
                }
            }

            if ($request->filled('nurse_id')) {
                // Only admins and the nurse themselves can filter by nurse
                if (in_array($user->role, ['admin', 'superadmin']) || 
                    ($user->role === 'nurse' && $user->id == $request->nurse_id)) {
                    $query->forNurse($request->nurse_id);
                }
            }

            // Date filtering - support both visit_date and created_at
            if ($request->filled('date')) {
                if ($request->get('date_type') === 'created') {
                    $query->whereDate('created_at', $request->date);
                } else {
                    $query->forDate($request->date); // Default to visit_date
                }
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                if ($request->get('date_type') === 'created') {
                    $query->whereBetween('created_at', [
                        $request->date_from . ' 00:00:00',
                        $request->date_to . ' 23:59:59'
                    ]);
                } else {
                    $query->forDateRange($request->date_from, $request->date_to); // Default to visit_date
                }
            }

            // Additional date filters for created_at specifically
            if ($request->filled('created_date')) {
                $query->whereDate('created_at', $request->created_date);
            }

            if ($request->filled('created_from') && $request->filled('created_to')) {
                $query->whereBetween('created_at', [
                    $request->created_from . ' 00:00:00',
                    $request->created_to . ' 23:59:59'
                ]);
            }

            if ($request->filled('condition')) {
                $query->byCondition($request->condition);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('recent_days')) {
                $query->recent($request->recent_days);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $progressNotes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $progressNotes->items(),
                'pagination' => [
                    'current_page' => $progressNotes->currentPage(),
                    'per_page' => $progressNotes->perPage(),
                    'total' => $progressNotes->total(),
                    'last_page' => $progressNotes->lastPage(),
                    'from' => $progressNotes->firstItem(),
                    'to' => $progressNotes->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress notes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created progress note.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Only nurses and admins can create progress notes
            if (!in_array($user->role, ['nurse', 'admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create progress notes.',
                ], 403);
            }

            // Validate the request
            $validator = Validator::make(
                $request->all(),
                ProgressNote::validationRules(),
                ProgressNote::validationMessages()
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate that patient and nurse exist and have correct roles
            $patient = User::find($request->patient_id);
            $nurse = User::find($request->nurse_id);

            if (!$patient || $patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient selected.'
                ], 422);
            }

            if (!$nurse || $nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid nurse selected.'
                ], 422);
            }

            // Nurses can only create notes for themselves
            if ($user->role === 'nurse' && $user->id !== $request->nurse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only create progress notes for yourself.',
                ], 403);
            }

            // Check if nurse has access to this patient
            if (!$this->canAccessPatientData($user, $request->patient_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this patient.',
                ], 403);
            }

            // Check for duplicate note on same date/time
            $existingNote = ProgressNote::where([
                'patient_id' => $request->patient_id,
                'nurse_id' => $request->nurse_id,
                'visit_date' => $request->visit_date,
                'visit_time' => $request->visit_time
            ])->first();

            if ($existingNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'A progress note already exists for this patient, nurse, date, and time combination.'
                ], 422);
            }

            // Create the progress note
            $progressNote = ProgressNote::create($request->all());

            // Load relationships for response
            $progressNote->load(['patient', 'nurse']);

            return response()->json([
                'success' => true,
                'message' => 'Progress note created successfully.',
                'data' => $progressNote
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create progress note.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified progress note.
     */
    public function show(ProgressNote $progressNote): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check if user can access this progress note
            if (!$this->canAccessProgressNote($user, $progressNote)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this progress note.',
                ], 403);
            }

            $progressNote->load(['patient', 'nurse']);

            return response()->json([
                'success' => true,
                'data' => $progressNote
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress note.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified progress note.
     */
    public function update(Request $request, ProgressNote $progressNote): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check if user can edit this progress note
            if (!$this->canEditProgressNote($user, $progressNote)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit this progress note.',
                ], 403);
            }

            // Validate the request
            $validator = Validator::make(
                $request->all(),
                ProgressNote::validationRules(true),
                ProgressNote::validationMessages()
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate roles if patient_id or nurse_id changed
            if ($request->filled('patient_id') && $request->patient_id != $progressNote->patient_id) {
                $patient = User::find($request->patient_id);
                if (!$patient || $patient->role !== 'patient') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid patient selected.'
                    ], 422);
                }

                // Check access to new patient
                if (!$this->canAccessPatientData($user, $request->patient_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this patient.',
                    ], 403);
                }
            }

            if ($request->filled('nurse_id') && $request->nurse_id != $progressNote->nurse_id) {
                $nurse = User::find($request->nurse_id);
                if (!$nurse || $nurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid nurse selected.'
                    ], 422);
                }

                // Nurses can only assign notes to themselves
                if ($user->role === 'nurse' && $user->id !== $request->nurse_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only assign progress notes to yourself.',
                    ], 403);
                }
            }

            // Check for duplicate if critical fields changed
            if ($request->filled(['patient_id', 'nurse_id', 'visit_date', 'visit_time'])) {
                $existingNote = ProgressNote::where([
                    'patient_id' => $request->patient_id,
                    'nurse_id' => $request->nurse_id,
                    'visit_date' => $request->visit_date,
                    'visit_time' => $request->visit_time
                ])->where('id', '!=', $progressNote->id)->first();

                if ($existingNote) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A progress note already exists for this patient, nurse, date, and time combination.'
                    ], 422);
                }
            }

            // Update the progress note
            $progressNote->update($request->all());

            // Load relationships for response
            $progressNote->load(['patient', 'nurse']);

            return response()->json([
                'success' => true,
                'message' => 'Progress note updated successfully.',
                'data' => $progressNote
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress note.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified progress note.
     */
    public function destroy(ProgressNote $progressNote): JsonResponse
    {
        try {
            $user = auth()->user();

            // Only admins can delete progress notes (or nurse who created it within 24 hours)
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                // Allow nurse to delete their own notes within 24 hours
                if ($user->role === 'nurse' && 
                    $progressNote->nurse_id === $user->id && 
                    $progressNote->created_at->diffInHours(now()) <= 24) {
                    // Allowed
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to delete this progress note.',
                    ], 403);
                }
            }

            $progressNote->delete();

            return response()->json([
                'success' => true,
                'message' => 'Progress note deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete progress note.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export progress notes to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = auth()->user();
        
        // Build query with role-based filtering
        $query = ProgressNote::with(['patient', 'nurse'])->ordered();
        $this->applyRoleBasedFiltering($query, $user);

        // Apply filters similar to index method
        if ($request->filled('patient_id') && $this->canAccessPatientData($user, $request->patient_id)) {
            $query->forPatient($request->patient_id);
        }

        if ($request->filled('nurse_id')) {
            if (in_array($user->role, ['admin', 'superadmin']) || 
                ($user->role === 'nurse' && $user->id == $request->nurse_id)) {
                $query->forNurse($request->nurse_id);
            }
        }

        // Apply other filters
        if ($request->filled('date')) {
            if ($request->get('date_type') === 'created') {
                $query->whereDate('created_at', $request->date);
            } else {
                $query->forDate($request->date);
            }
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            if ($request->get('date_type') === 'created') {
                $query->whereBetween('created_at', [
                    $request->date_from . ' 00:00:00',
                    $request->date_to . ' 23:59:59'
                ]);
            } else {
                $query->forDateRange($request->date_from, $request->date_to);
            }
        }

        if ($request->filled('condition')) {
            $query->byCondition($request->condition);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $filename = 'progress_notes_' . $user->role . '_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'ID',
                'Patient Name',
                'Patient ID',
                'Nurse Name',
                'Nurse License',
                'Visit Date',
                'Visit Time',
                'Temperature (°C)',
                'Pulse (bpm)',
                'Respiration (/min)',
                'Blood Pressure',
                'SpO₂ (%)',
                'General Condition',
                'Pain Level',
                'Interventions',
                'Wound Status',
                'Other Observations',
                'Education Provided',
                'Family Concerns',
                'Next Steps',
                'Signed At',
                'Created At',
                'Updated At'
            ]);

            // Process in chunks to handle large datasets
            $query->chunk(1000, function ($progressNotes) use ($handle) {
                foreach ($progressNotes as $note) {
                    // Format interventions
                    $interventions = $note->getInterventionsList();
                    $interventionsText = collect($interventions)->map(function ($intervention) {
                        return $intervention['type'] . ': ' . $intervention['details'];
                    })->implode('; ');

                    fputcsv($handle, [
                        $note->id,
                        $note->patient_name,
                        $note->patient->ghana_card_number ?? '',
                        $note->nurse_name,
                        $note->nurse_license ?? '',
                        $note->visit_date->format('Y-m-d'),
                        $note->visit_time ? $note->visit_time->format('H:i') : '',
                        $note->vitals['temperature'] ?? '',
                        $note->vitals['pulse'] ?? '',
                        $note->vitals['respiration'] ?? '',
                        $note->vitals['blood_pressure'] ?? '',
                        $note->vitals['spo2'] ?? '',
                        $note->general_condition,
                        $note->pain_level,
                        $interventionsText,
                        $note->wound_status ?? '',
                        $note->other_observations ?? '',
                        $note->education_provided ?? '',
                        $note->family_concerns ?? '',
                        $note->next_steps ?? '',
                        $note->signed_at ? $note->signed_at->format('Y-m-d H:i:s') : '',
                        $note->created_at->format('Y-m-d H:i:s'),
                        $note->updated_at->format('Y-m-d H:i:s')
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get statistics for dashboard/reporting.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            // Build base query with role-based filtering
            $baseQuery = ProgressNote::forDateRange($startDate, $endDate);
            $this->applyRoleBasedFiltering($baseQuery, $user);

            $stats = [
                'total_notes' => (clone $baseQuery)->count(),
                'unique_patients' => (clone $baseQuery)->distinct('patient_id')->count('patient_id'),
                'unique_nurses' => (clone $baseQuery)->distinct('nurse_id')->count('nurse_id'),
                'condition_breakdown' => (clone $baseQuery)
                    ->select('general_condition', DB::raw('count(*) as count'))
                    ->groupBy('general_condition')
                    ->pluck('count', 'general_condition'),
                'pain_level_average' => (clone $baseQuery)->avg('pain_level'),
                'notes_with_abnormal_vitals' => (clone $baseQuery)->get()
                    ->filter(function ($note) {
                        return $note->hasAbnormalVitals();
                    })->count(),
                'daily_notes_count' => (clone $baseQuery)
                    ->select('visit_date', DB::raw('count(*) as count'))
                    ->groupBy('visit_date')
                    ->orderBy('visit_date')
                    ->pluck('count', 'visit_date'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get progress notes for a specific patient.
     */
    public function getPatientNotes(User $patient, Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a patient.'
                ], 422);
            }

            // Check if user can access this patient's data
            if (!$this->canAccessPatientData($user, $patient->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this patient\'s data.',
                ], 403);
            }

            $query = ProgressNote::forPatient($patient->id)
                ->with(['nurse'])
                ->ordered();

            if ($request->filled('limit')) {
                $query->limit($request->limit);
            }

            $progressNotes = $query->get();

            return response()->json([
                'success' => true,
                'data' => $progressNotes,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'ghana_card' => $patient->ghana_card_number
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patient notes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get progress notes created by a specific nurse.
     */
    public function getNurseNotes(User $nurse, Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a nurse.'
                ], 422);
            }

            // Only admins or the nurse themselves can access nurse-specific notes
            if (!in_array($user->role, ['admin', 'superadmin']) && $user->id !== $nurse->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this nurse\'s notes.',
                ], 403);
            }

            $query = ProgressNote::forNurse($nurse->id)
                ->with(['patient'])
                ->ordered();

            // Apply additional filtering based on user role
            if ($user->role === 'nurse' && $user->id === $nurse->id) {
                // Nurse can see all their own notes (already filtered by forNurse)
            } elseif ($user->role === 'doctor') {
                // Doctor can only see notes for patients in their care plans
                $query->whereHas('patient', function($patientQuery) use ($user) {
                    $patientQuery->whereHas('carePlansAsPatient', function($carePlanQuery) use ($user) {
                        $carePlanQuery->where('doctor_id', $user->id);
                    });
                });
            }

            if ($request->filled('limit')) {
                $query->limit($request->limit);
            }

            $progressNotes = $query->get();

            return response()->json([
                'success' => true,
                'data' => $progressNotes,
                'nurse' => [
                    'id' => $nurse->id,
                    'name' => $nurse->first_name . ' ' . $nurse->last_name,
                    'license' => $nurse->license_number
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nurse notes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Duplicate a progress note (for template/routine visits).
     */
    public function duplicate(ProgressNote $progressNote): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check if user can access the original note
            if (!$this->canAccessProgressNote($user, $progressNote)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this progress note.',
                ], 403);
            }

            // Check if user can create notes for this patient
            if (!$this->canAccessPatientData($user, $progressNote->patient_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this patient.',
                ], 403);
            }

            $newNoteData = $progressNote->toArray();
            
            // Remove fields that shouldn't be duplicated
            unset($newNoteData['id']);
            unset($newNoteData['created_at']);
            unset($newNoteData['updated_at']);
            unset($newNoteData['deleted_at']);
            unset($newNoteData['signed_at']);
            
            // Reset visit date and time to current
            $newNoteData['visit_date'] = Carbon::now()->toDateString();
            $newNoteData['visit_time'] = Carbon::now()->format('H:i');
            
            // For nurses, ensure they're assigning to themselves
            if ($user->role === 'nurse') {
                $newNoteData['nurse_id'] = $user->id;
            }
            
            // Clear specific observations that are visit-specific
            $newNoteData['wound_status'] = null;
            $newNoteData['other_observations'] = null;
            $newNoteData['family_concerns'] = null;
            
            // Create new note
            $newNote = ProgressNote::create($newNoteData);
            $newNote->load(['patient', 'nurse']);

            return response()->json([
                'success' => true,
                'message' => 'Progress note duplicated successfully.',
                'data' => $newNote
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate progress note.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Debug endpoint to check available dates and data
     */
    public function debug(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Apply role-based filtering to debug data too
            $baseQuery = ProgressNote::query();
            $this->applyRoleBasedFiltering($baseQuery, $user);

            $data = [
                'total_notes' => (clone $baseQuery)->count(),
                'available_visit_dates' => (clone $baseQuery)->select('visit_date')
                    ->distinct()
                    ->orderBy('visit_date', 'desc')
                    ->limit(20)
                    ->pluck('visit_date')
                    ->map(fn($date) => $date->format('Y-m-d')),
                'available_created_dates' => (clone $baseQuery)->selectRaw('DATE(created_at) as created_date')
                    ->distinct()
                    ->orderBy('created_date', 'desc')
                    ->limit(20)
                    ->pluck('created_date'),
                'recent_notes' => (clone $baseQuery)->with(['patient', 'nurse'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($note) {
                        return [
                            'id' => $note->id,
                            'patient' => $note->patient_name,
                            'nurse' => $note->nurse_name,
                            'visit_date' => $note->visit_date->format('Y-m-d'),
                            'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                'filter_test' => null,
                'user_role' => $user->role
            ];

            // Test specific date filter if provided
            if ($request->filled('test_date')) {
                $testDate = $request->test_date;
                $visitDateQuery = clone $baseQuery;
                $createdDateQuery = clone $baseQuery;
                
                $visitDateResults = $visitDateQuery->whereDate('visit_date', $testDate)->count();
                $createdDateResults = $createdDateQuery->whereDate('created_at', $testDate)->count();
                
                $data['filter_test'] = [
                    'test_date' => $testDate,
                    'visit_date_results' => $visitDateResults,
                    'created_date_results' => $createdDateResults,
                    'notes_on_visit_date' => (clone $baseQuery)->whereDate('visit_date', $testDate)
                        ->with(['patient', 'nurse'])
                        ->get()
                        ->map(function($note) {
                            return [
                                'id' => $note->id,
                                'patient' => $note->patient_name,
                                'visit_date' => $note->visit_date->format('Y-m-d'),
                                'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                            ];
                        }),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Debug failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply role-based filtering to progress note queries
     */
    private function applyRoleBasedFiltering($query, $user)
    {
        switch ($user->role) {
            case 'patient':
                // Patients can only see their own progress notes
                $query->where('patient_id', $user->id);
                break;
            case 'nurse':
                // Nurses can see progress notes they created or for patients they're assigned to
                $query->where(function($q) use ($user) {
                    $q->where('nurse_id', $user->id)
                      ->orWhereHas('patient', function($patientQuery) use ($user) {
                          $patientQuery->whereHas('carePlansAsPatient', function($carePlanQuery) use ($user) {
                              $carePlanQuery->where('primary_nurse_id', $user->id)
                                          ->orWhere('secondary_nurse_id', $user->id);
                          });
                      });
                });
                break;
            case 'doctor':
                // Doctors can see progress notes for patients in their care plans
                $query->whereHas('patient', function($patientQuery) use ($user) {
                    $patientQuery->whereHas('carePlansAsPatient', function($carePlanQuery) use ($user) {
                        $carePlanQuery->where('doctor_id', $user->id);
                    });
                });
                break;
            case 'admin':
            case 'superadmin':
                // Admin and superadmin can see all progress notes (no filtering)
                break;
        }
    }

    /**
     * Check if user can access a specific progress note
     */
    private function canAccessProgressNote($user, $progressNote): bool
    {
        switch ($user->role) {
            case 'patient':
                return $progressNote->patient_id === $user->id;
            case 'nurse':
                // Can access if they created it or are assigned to the patient
                if ($progressNote->nurse_id === $user->id) {
                    return true;
                }
                return $progressNote->patient->carePlansAsPatient()
                    ->where(function($q) use ($user) {
                        $q->where('primary_nurse_id', $user->id)
                          ->orWhere('secondary_nurse_id', $user->id);
                    })->exists();
            case 'doctor':
                return $progressNote->patient->carePlansAsPatient()
                    ->where('doctor_id', $user->id)->exists();
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if user can edit a specific progress note
     */
    private function canEditProgressNote($user, $progressNote): bool
    {
        switch ($user->role) {
            case 'nurse':
                return $progressNote->nurse_id === $user->id;
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if user can access a specific patient's data
     */
    private function canAccessPatientData($user, $patientId): bool
    {
        switch ($user->role) {
            case 'patient':
                return $user->id == $patientId;
            case 'nurse':
                // Can access if assigned to patient's care plan
                return CarePlan::where('patient_id', $patientId)
                    ->where(function($q) use ($user) {
                        $q->where('primary_nurse_id', $user->id)
                          ->orWhere('secondary_nurse_id', $user->id);
                    })->exists();
            case 'doctor':
                return CarePlan::where('patient_id', $patientId)
                    ->where('doctor_id', $user->id)->exists();
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }
}