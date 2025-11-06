<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProgressNote;
use App\Models\User;
use App\Models\CarePlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProgressNoteController extends Controller
{
    /**
     * Get progress notes based on user role
     * GET /api/mobile/progress-notes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $perPage = $request->input('per_page', 15);
            $sortOrder = $request->input('sort_order', 'desc'); 
            
            $query = $this->buildProgressNoteQuery($user);
            $this->applyFilters($query, $request);
            

            $notes = $query->orderBy('visit_date', $sortOrder)
                        ->orderBy('visit_time', $sortOrder)
                        ->paginate($perPage);
            
            $transformedNotes = $notes->getCollection()->map(function($note) use ($user) {
                return $this->transformProgressNote($note, $user->role, true);
            });
            
            $notes->setCollection($transformedNotes);
            
            return response()->json([
                'success' => true,
                'data' => $notes->items(),
                'pagination' => [
                    'current_page' => $notes->currentPage(),
                    'last_page' => $notes->lastPage(),
                    'per_page' => $notes->perPage(),
                    'total' => $notes->total(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching progress notes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress notes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get a specific progress note
     * GET /api/mobile/progress-notes/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();

            $note = ProgressNote::with(['patient', 'nurse'])->find($id);

            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'Progress note not found.'
                ], 404);
            }

            // Authorization check
            if (!$this->userCanAccessNote($user, $note)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformProgressNote($note, $user->role, true)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching progress note: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress note.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a new progress note (Nurse only)
     * POST /api/mobile/progress-notes
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can create progress notes.'
                ], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:users,id',
                'visit_date' => 'required|date',
                'visit_time' => 'required|date_format:H:i',
                'vitals' => 'nullable|array',
                'vitals.temperature' => 'nullable|numeric|min:0|max:50',
                'vitals.pulse' => 'nullable|integer|min:0|max:300',
                'vitals.respiration' => 'nullable|integer|min:0|max:100',
                'vitals.blood_pressure' => 'nullable|string|max:20',
                'vitals.spo2' => 'nullable|integer|min:0|max:100',
                'interventions' => 'nullable|array',
                'general_condition' => 'required|in:Stable,Improving,Declining,Critical',
                'pain_level' => 'required|integer|min:0|max:10',
                'wound_status' => 'nullable|string',
                'other_observations' => 'nullable|string',
                'education_provided' => 'nullable|string',
                'family_concerns' => 'nullable|string',
                'next_steps' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Verify patient exists
            $patient = User::where('id', $validated['patient_id'])
                ->where('role', 'patient')
                ->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found.'
                ], 404);
            }

            // Check nurse has access to patient
            $hasAccess = CarePlan::where('patient_id', $validated['patient_id'])
                ->where(function($query) use ($nurse) {
                    $query->where('primary_nurse_id', $nurse->id)
                        ->orWhere('secondary_nurse_id', $nurse->id);
                })
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this patient.'
                ], 403);
            }

            // Check for duplicate
            $existingNote = ProgressNote::where([
                'patient_id' => $validated['patient_id'],
                'nurse_id' => $nurse->id,
                'visit_date' => $validated['visit_date'],
                'visit_time' => $validated['visit_time']
            ])->first();

            if ($existingNote) {
                return response()->json([
                    'success' => false,
                    'message' => 'A progress note already exists for this date and time.'
                ], 422);
            }

            // Map general condition
            $conditionMap = [
                'Stable' => 'stable',
                'Improving' => 'improved',
                'Declining' => 'deteriorating',
                'Critical' => 'deteriorating'
            ];

            // Create progress note
            $progressNote = ProgressNote::create([
                'patient_id' => $validated['patient_id'],
                'nurse_id' => $nurse->id,
                'visit_date' => $validated['visit_date'],
                'visit_time' => $validated['visit_time'],
                'vitals' => $validated['vitals'] ?? null,
                'interventions' => $validated['interventions'] ?? null,
                'general_condition' => $conditionMap[$validated['general_condition']] ?? 'stable',
                'pain_level' => $validated['pain_level'],
                'wound_status' => $validated['wound_status'] ?? null,
                'other_observations' => $validated['other_observations'] ?? null,
                'education_provided' => $validated['education_provided'] ?? null,
                'family_concerns' => $validated['family_concerns'] ?? null,
                'next_steps' => $validated['next_steps'] ?? null,
                'signed_at' => now(),
                'signature_method' => 'digital'
            ]);

            $progressNote->load(['patient', 'nurse']);

            return response()->json([
                'success' => true,
                'message' => 'Progress note created successfully.',
                'data' => $this->transformProgressNote($progressNote, $nurse->role, true)
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating progress note: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create progress note.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Build progress note query based on user role
     */
    private function buildProgressNoteQuery($user)
    {
        $query = ProgressNote::with(['patient:id,first_name,last_name', 'nurse:id,first_name,last_name']);

        if ($user->role === 'nurse') {
            $query->where('nurse_id', $user->id);
        } elseif ($user->role === 'patient') {
            $query->where('patient_id', $user->id);
        }

        return $query;
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('visit_date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('other_observations', 'like', "%{$search}%")
                  ->orWhere('next_steps', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Transform progress note for API response
     */
    private function transformProgressNote($note, $userRole, $detailed = false): array
    {
        $data = [
            'id' => $note->id,
            'visit_date' => $note->visit_date,
            'visit_time' => \Carbon\Carbon::parse($note->visit_time)->format('H:i'),
            'general_condition' => ucfirst($note->general_condition),
            'pain_level' => $note->pain_level,
        ];

        // Add patient info for nurses
        if ($userRole === 'nurse' && $note->patient) {
            $data['patient'] = [
                'id' => $note->patient->id,
                'name' => $note->patient->first_name . ' ' . $note->patient->last_name,
            ];
        }

        // Add nurse info for patients
        if ($userRole === 'patient' && $note->nurse) {
            $data['nurse'] = [
                'id' => $note->nurse->id,
                'name' => $note->nurse->first_name . ' ' . $note->nurse->last_name,
            ];
        }

        // Add detailed info if requested
        if ($detailed) {
            $data['vitals'] = $note->vitals;
            $data['interventions'] = $note->interventions;
            $data['wound_status'] = $note->wound_status;
            $data['other_observations'] = $note->other_observations;
            $data['education_provided'] = $note->education_provided;
            $data['family_concerns'] = $note->family_concerns;
            $data['next_steps'] = $note->next_steps;
            $data['created_at'] = $note->created_at?->toIso8601String();
        }

        return $data;
    }

    /**
     * Check if user can access note
     */
    private function userCanAccessNote($user, $note): bool
    {
        if ($user->role === 'nurse') {
            return $note->nurse_id === $user->id;
        }
        
        if ($user->role === 'patient') {
            return $note->patient_id === $user->id;
        }

        return false;
    }
}