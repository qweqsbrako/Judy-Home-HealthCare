<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\User;
use App\Models\CarePlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $query = Schedule::with(['nurse', 'carePlan.patient', 'createdBy']);

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $query->latest('schedule_date');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $statuses = is_array($request->status) 
                ? $request->status 
                : explode(',', $request->status);
            
            $query->whereIn('status', $statuses);
        }

        // Filter by shift type
        if ($request->has('shift_type') && $request->shift_type !== 'all') {
            $query->where('shift_type', $request->shift_type);
        }

        // Filter by nurse (only for admin/superadmin)
        if ($request->has('nurse_id') && $request->nurse_id !== 'all' && in_array($user->role, ['admin', 'superadmin'])) {
            $query->where('nurse_id', $request->nurse_id);
        }

        // Filter by care plan
        if ($request->has('care_plan_id') && $request->care_plan_id !== 'all') {
            // For doctors, ensure they can only filter their own care plans
            if ($user->role === 'doctor') {
                $query->where('care_plan_id', $request->care_plan_id)
                      ->whereHas('carePlan', function($q) use ($user) {
                          $q->where('doctor_id', $user->id);
                      });
            } else {
                $query->where('care_plan_id', $request->care_plan_id);
            }
        }

        // Date range filters
        if ($request->has('start_date') && $request->start_date) {
            $query->where('schedule_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('schedule_date', '<=', $request->end_date);
        }

        // Today's schedules
        if ($request->has('view') && $request->view === 'today') {
            $query->where('schedule_date', today());
        }

        // Upcoming schedules
        if ($request->has('view') && $request->view === 'upcoming') {
            $query->where('schedule_date', '>=', today());
        }

        // This week's schedules
        if ($request->has('view') && $request->view === 'this_week') {
            $query->whereBetween('schedule_date', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('shift_notes', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('nurse', function($nq) use ($search) {
                      $nq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('carePlan', function($cq) use ($search) {
                      $cq->where('title', 'like', "%{$search}%")
                        ->orWhereHas('patient', function($pq) use ($search) {
                            $pq->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                  });
            });
        }

        $schedules = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'filters' => [
                'shift_types' => Schedule::getShiftTypes(),
                'statuses' => Schedule::getStatuses(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'nurse_id' => 'required|exists:users,id',
            'care_plan_id' => 'nullable|exists:care_plans,id',
            'schedule_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'shift_type' => ['required', Rule::in(array_keys(Schedule::getShiftTypes()))],
            'location' => 'nullable|string|max:255',
            'shift_notes' => 'nullable|string|max:1000',
        ]);

        // Verify the nurse exists and has the right role
        $nurse = User::where('id', $validated['nurse_id'])
            ->where('role', 'nurse')
            ->where('is_active', true)
            ->first();

        if (!$nurse) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid nurse selected.',
            ], 422);
        }

        // Verify care plan if provided and check permissions
        if (!empty($validated['care_plan_id'])) {
            $carePlan = CarePlan::where('id', $validated['care_plan_id'])
                ->where('status', 'active')
                ->first();

            if (!$carePlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid care plan selected.',
                ], 422);
            }

            // Check if user has permission to create schedules for this care plan
            if (!$this->canAccessCarePlan($user, $carePlan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create schedules for this care plan.',
                ], 403);
            }

            // Check if nurse is assigned to this care plan
            if (!$carePlan->isNurseAssigned($validated['nurse_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nurse is not assigned to this care plan.',
                ], 422);
            }
        }

        // Check for scheduling conflicts
        $conflictingSchedule = Schedule::where('nurse_id', $validated['nurse_id'])
            ->where('schedule_date', $validated['schedule_date'])
            ->where(function ($q) {
                $q->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'completed');
            })
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                ->orWhere(function ($subQ) use ($validated) {
                    $subQ->where('start_time', '<=', $validated['start_time'])
                        ->where('end_time', '>=', $validated['end_time']);
                });
            })
            ->first();

        if ($conflictingSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Nurse has a conflicting schedule at this time.',
                'conflict' => [
                    'existing_schedule_id' => $conflictingSchedule->id,
                    'time_slot' => $conflictingSchedule->formatted_time_slot,
                    'shift_type' => $conflictingSchedule->formatted_shift_type
                ]
            ], 422);
        }

        $validated['created_by'] = $user->id;
        $schedule = Schedule::create($validated);
        $schedule->load(['nurse', 'carePlan.patient', 'createdBy']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully.',
            'data' => $schedule
        ], 201);
    }

    public function show(Schedule $schedule): JsonResponse
    {
        $user = auth()->user();

        // Check permissions
        if (!$this->canAccessSchedule($user, $schedule)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this schedule.',
            ], 403);
        }

        $schedule->load(['nurse', 'carePlan.patient', 'createdBy']);

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        $user = auth()->user();

        // Check permissions
        if (!$this->canEditSchedule($user, $schedule)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this schedule.',
            ], 403);
        }

        // Only allow updates for certain statuses
        if (!in_array($schedule->status, ['scheduled', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify schedule in current status.',
            ], 422);
        }

        $validated = $request->validate([
            'care_plan_id' => 'nullable|exists:care_plans,id',
            'schedule_date' => 'sometimes|date|after_or_equal:today',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'shift_type' => ['sometimes', Rule::in(array_keys(Schedule::getShiftTypes()))],
            'location' => 'nullable|string|max:255',
            'shift_notes' => 'nullable|string|max:1000',
        ]);

        // Verify care plan if provided and check permissions
        if (!empty($validated['care_plan_id'])) {
            $carePlan = CarePlan::where('id', $validated['care_plan_id'])
                ->where('status', 'active')
                ->first();

            if (!$carePlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid care plan selected.',
                ], 422);
            }

            // Check if user has permission to assign this care plan
            if (!$this->canAccessCarePlan($user, $carePlan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to assign this care plan.',
                ], 403);
            }

            // Check if nurse is assigned to this care plan
            if (!$carePlan->isNurseAssigned($schedule->nurse_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nurse is not assigned to this care plan.',
                ], 422);
            }
        }

        // Check for conflicts if date/time changed
        if (isset($validated['schedule_date']) || isset($validated['start_time']) || isset($validated['end_time'])) {
            $checkDate = $validated['schedule_date'] ?? $schedule->schedule_date;
            $checkStartTime = $validated['start_time'] ?? $schedule->start_time;
            $checkEndTime = $validated['end_time'] ?? $schedule->end_time;

            $conflictingSchedule = Schedule::where('nurse_id', $schedule->nurse_id)
                ->where('id', '!=', $schedule->id)
                ->where('schedule_date', $checkDate)
                ->where('status', '!=', 'cancelled')
                ->where(function($q) use ($checkStartTime, $checkEndTime) {
                    $q->whereBetween('start_time', [$checkStartTime, $checkEndTime])
                      ->orWhereBetween('end_time', [$checkStartTime, $checkEndTime])
                      ->orWhere(function($subQ) use ($checkStartTime, $checkEndTime) {
                          $subQ->where('start_time', '<=', $checkStartTime)
                               ->where('end_time', '>=', $checkEndTime);
                      });
                })
                ->first();

            if ($conflictingSchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nurse has a conflicting schedule at this time.',
                ], 422);
            }

            // Reset confirmation if schedule changed
            $validated['nurse_confirmed_at'] = null;
        }

        $schedule->update($validated);
        $schedule->load(['nurse', 'carePlan.patient', 'createdBy']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.',
            'data' => $schedule
        ]);
    }


    public function destroy(Schedule $schedule): JsonResponse
    {
        $user = auth()->user();

        // Check permissions
        if (!$this->canEditSchedule($user, $schedule)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this schedule.',
            ], 403);
        }

        // Only allow deletion if not started or completed
        if (in_array($schedule->status, ['in_progress', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete schedule that is in progress or completed.',
            ], 422);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully.'
        ]);
    }

        /**
     * Apply role-based filtering to schedule queries
     */
    private function applyRoleBasedFiltering($query, $user)
    {
        switch ($user->role) {
            case 'doctor':
                // Doctors can only see schedules for their care plans
                $query->whereHas('carePlan', function($q) use ($user) {
                    $q->where('doctor_id', $user->id);
                });
                break;
            case 'nurse':
                // Nurses can only see their own schedules
                $query->where('nurse_id', $user->id);
                break;
            case 'patient':
                // Patients can only see schedules for their care plans
                $query->whereHas('carePlan', function($q) use ($user) {
                    $q->where('patient_id', $user->id);
                });
                break;
            case 'admin':
            case 'superadmin':
                // Admin and superadmin can see all schedules (no filtering)
                break;
        }
    }

    /**
     * Check if user can access a schedule
     */
    private function canAccessSchedule($user, $schedule): bool
    {
        switch ($user->role) {
            case 'doctor':
                return $schedule->carePlan && $schedule->carePlan->doctor_id === $user->id;
            case 'nurse':
                return $schedule->nurse_id === $user->id;
            case 'patient':
                return $schedule->carePlan && $schedule->carePlan->patient_id === $user->id;
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if user can edit a schedule
     */
    private function canEditSchedule($user, $schedule): bool
    {
        switch ($user->role) {
            case 'doctor':
                return $schedule->carePlan && $schedule->carePlan->doctor_id === $user->id;
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if user can access a care plan
     */
    private function canAccessCarePlan($user, $carePlan): bool
    {
        switch ($user->role) {
            case 'doctor':
                return $carePlan->doctor_id === $user->id;
            case 'nurse':
                return $carePlan->primary_nurse_id === $user->id || $carePlan->secondary_nurse_id === $user->id;
            case 'patient':
                return $carePlan->patient_id === $user->id;
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }


    public function confirmByNurse(Schedule $schedule): JsonResponse
    {
        $user = auth()->user();
        
        // Only nurses can confirm their own schedules
        if ($user->role !== 'nurse' || $schedule->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only confirm your own schedules.',
            ], 403);
        }

        if ($schedule->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Only scheduled shifts can be confirmed.',
            ], 422);
        }

        $schedule->confirmByNurse();
        $schedule->update(['status' => 'confirmed']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule confirmed by nurse.',
            'data' => $schedule
        ]);
    }

    public function startShift(Schedule $schedule): JsonResponse
    {
        $user = auth()->user();
        
        // Only nurses can start their own shifts
        if ($user->role !== 'nurse' || $schedule->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only start your own shifts.',
            ], 403);
        }

        if (!in_array($schedule->status, ['scheduled', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start shift for schedule in current status.',
            ], 422);
        }

        $schedule->startShift();

        return response()->json([
            'success' => true,
            'message' => 'Shift started successfully.',
            'data' => $schedule
        ]);
    }

    public function endShift(Request $request, Schedule $schedule): JsonResponse
    {
        $user = auth()->user();
        
        // Only nurses can end their own shifts
        if ($user->role !== 'nurse' || $schedule->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only end your own shifts.',
            ], 403);
        }

        $validated = $request->validate([
            'shift_notes' => 'nullable|string|max:1000',
        ]);

        if ($schedule->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Only shifts in progress can be ended.',
            ], 422);
        }

        if (isset($validated['shift_notes'])) {
            $schedule->update(['shift_notes' => $validated['shift_notes']]);
        }

        $schedule->endShift();

        return response()->json([
            'success' => true,
            'message' => 'Shift ended successfully.',
            'data' => $schedule
        ]);
    }

    public function cancel(Request $request, Schedule $schedule): JsonResponse
    {
        $user = auth()->user();

        // Check permissions - only admins or assigned nurses can cancel
        if (!in_array($user->role, ['admin', 'superadmin']) && 
            !($user->role === 'nurse' && $schedule->nurse_id === $user->id) &&
            !($user->role === 'doctor' && $schedule->carePlan && $schedule->carePlan->doctor_id === $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel this schedule.',
            ], 403);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        if (in_array($schedule->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed or already cancelled shifts.',
            ], 422);
        }

        $schedule->cancel($validated['cancellation_reason']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule cancelled successfully.',
            'data' => $schedule
        ]);
    }

        /**
     * Send reminder for a specific schedule
     */
    public function sendReminder(Schedule $schedule): JsonResponse
    {
        // Check if schedule is in appropriate status
        if (!in_array($schedule->status, ['scheduled', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send reminder for schedule in current status.',
            ], 422);
        }

        // Check if schedule is in the future
        if ($schedule->schedule_date->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send reminder for past schedules.',
            ], 422);
        }

        // Send reminder via both email and SMS
        $results = $schedule->sendReminder();

        // Determine response based on results
        if ($results['overall_success']) {
            $message = 'Reminder sent successfully';
            $details = [];

            if ($results['email']['sent'] && $results['sms']['sent']) {
                $details[] = 'Email and SMS sent';
            } elseif ($results['email']['sent']) {
                $details[] = 'Email sent, SMS failed';
            } elseif ($results['sms']['sent']) {
                $details[] = 'SMS sent, Email failed';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'details' => $details,
                'data' => [
                    'schedule_id' => $schedule->id,
                    'reminder_count' => $schedule->reminder_count,
                    'last_reminder_sent' => $schedule->last_reminder_sent,
                    'email_sent' => $results['email']['sent'],
                    'sms_sent' => $results['sms']['sent'],
                    'email_message' => $results['email']['message'],
                    'sms_message' => $results['sms']['message']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send reminder through all channels',
            'data' => [
                'schedule_id' => $schedule->id,
                'email_sent' => $results['email']['sent'],
                'sms_sent' => $results['sms']['sent'],
                'email_message' => $results['email']['message'],
                'sms_message' => $results['sms']['message']
            ]
        ], 500);
    }

    /**
     * Send bulk reminders for upcoming schedules
     */
    public function sendBulkReminders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_ids' => 'sometimes|array',
            'schedule_ids.*' => 'exists:schedules,id',
            'hours_before' => 'sometimes|integer|min:1|max:72',
            'nurse_id' => 'sometimes|exists:users,id'
        ]);

        // Build query for schedules to remind
        $query = Schedule::with(['nurse', 'carePlan.patient'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('schedule_date', '>=', now());

        // Filter by specific schedule IDs if provided
        if (isset($validated['schedule_ids'])) {
            $query->whereIn('id', $validated['schedule_ids']);
        }

        // Filter by hours before schedule
        if (isset($validated['hours_before'])) {
            $hoursFromNow = now()->addHours((int) $validated['hours_before']);
            $query->where('schedule_date', '<=', $hoursFromNow);
        }

        // Filter by nurse
        if (isset($validated['nurse_id'])) {
            $query->where('nurse_id', $validated['nurse_id']);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No schedules found matching the criteria.',
            ], 404);
        }

        // Send bulk reminders
        $summary = Schedule::sendBulkReminders($schedules);

        return response()->json([
            'success' => $summary['success'] > 0,
            'message' => "Sent reminders for {$summary['success']} out of {$summary['total']} schedules",
            'data' => $summary
        ]);
    }

    /**
     * Get reminders that should be sent (for scheduled job)
     */
    public function getPendingReminders(Request $request): JsonResponse
    {
        $hoursAhead = (int) $request->get('hours_ahead', 24);

        $schedules = Schedule::with(['nurse', 'carePlan.patient'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('schedule_date', '>', now())
            ->where('schedule_date', '<=', now()->addHours($hoursAhead))
            ->where(function($query) {
                // Never sent reminder OR last reminder was more than 12 hours ago
                $query->whereNull('last_reminder_sent')
                      ->orWhere('last_reminder_sent', '<', now()->subHours(12));
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $schedules->count(),
                'schedules' => $schedules
            ]
        ]);
    }

    public function getCarePlansForNurse(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'nurse_id' => 'required|exists:users,id'
        ]);

        // Doctors can only get care plans for nurses assigned to their care plans
        $query = CarePlan::where('status', 'active')
            ->where(function($q) use ($validated) {
                $q->where('primary_nurse_id', $validated['nurse_id'])
                  ->orWhere('secondary_nurse_id', $validated['nurse_id']);
            });

        // Apply role-based filtering
        if ($user->role === 'doctor') {
            $query->where('doctor_id', $user->id);
        }

        $carePlans = $query->with(['patient:id,first_name,last_name'])
            ->select('id', 'title', 'patient_id', 'care_type', 'status', 'primary_nurse_id', 'secondary_nurse_id')
            ->orderBy('title')
            ->get()
            ->map(function($carePlan) {
                return [
                    'id' => $carePlan->id,
                    'plan_name' => $carePlan->title ?: 'Untitled Care Plan',
                    'title' => $carePlan->title ?: 'Untitled Care Plan',
                    'care_type' => $carePlan->care_type,
                    'status' => $carePlan->status,
                    'patient' => $carePlan->patient ? [
                        'id' => $carePlan->patient->id,
                        'first_name' => $carePlan->patient->first_name,
                        'last_name' => $carePlan->patient->last_name,
                    ] : null
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $carePlans
        ]);
    }

    public function getCalendarView(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'nurse_id' => 'nullable|exists:users,id',
        ]);

        $query = Schedule::with(['nurse', 'carePlan.patient'])
            ->whereBetween('schedule_date', [$validated['start_date'], $validated['end_date']]);

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        // Only admins can filter by any nurse
        if (isset($validated['nurse_id']) && in_array($user->role, ['admin', 'superadmin'])) {
            $query->where('nurse_id', $validated['nurse_id']);
        }

        $schedules = $query->get()
            ->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name . ' - ' . $schedule->formatted_shift_type,
                    'start' => $schedule->schedule_date_time->toISOString(),
                    'end' => $schedule->schedule_date->setTimeFromTimeString($schedule->end_time)->toISOString(),
                    'backgroundColor' => $this->getStatusColor($schedule->status),
                    'borderColor' => $this->getStatusColor($schedule->status),
                    'extendedProps' => [
                        'nurse_name' => $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name,
                        'shift_type' => $schedule->formatted_shift_type,
                        'status' => $schedule->status,
                        'location' => $schedule->location,
                        'is_confirmed' => $schedule->is_confirmed,
                        'care_plan' => $schedule->carePlan ? $schedule->carePlan->plan_name : null,
                        'patient_name' => $schedule->carePlan && $schedule->carePlan->patient 
                            ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name 
                            : null,
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    public function getStatistics(): JsonResponse
    {
        $user = auth()->user();
        
        // Build base query with role-based filtering
        $baseQuery = Schedule::query();
        $this->applyRoleBasedFiltering($baseQuery, $user);

        $stats = [
            'total_schedules' => (clone $baseQuery)->count(),
            'today_schedules' => (clone $baseQuery)->where('schedule_date', today())->count(),
            'upcoming_schedules' => (clone $baseQuery)->where('schedule_date', '>', today())->count(),
            'completed_schedules' => (clone $baseQuery)->where('status', 'completed')->count(),
            'confirmed_schedules' => (clone $baseQuery)->whereNotNull('nurse_confirmed_at')->count(),
            'by_status' => (clone $baseQuery)->select('status')
                ->selectRaw('count(*) as count')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function($item) {
                    $statuses = Schedule::getStatuses();
                    $statusLabel = $statuses[$item->status] ?? $item->status;
                    return [$statusLabel => $item->count];
                }),
            'by_shift_type' => (clone $baseQuery)->select('shift_type')
                ->selectRaw('count(*) as count')
                ->groupBy('shift_type')
                ->get()
                ->mapWithKeys(function($item) {
                    $shiftTypes = Schedule::getShiftTypes();
                    $shiftTypeLabel = $shiftTypes[$item->shift_type] ?? $item->shift_type;
                    return [$shiftTypeLabel => $item->count];
                }),
        ];

        // Calculate completion rate safely
        $pastSchedulesCount = (clone $baseQuery)->where('schedule_date', '<', today())->count();
        $completedPastSchedules = (clone $baseQuery)->where('schedule_date', '<', today())
            ->where('status', 'completed')
            ->count();
        
        $stats['completion_rate'] = $pastSchedulesCount > 0 
            ? ($completedPastSchedules / $pastSchedulesCount) * 100 
            : 0;

        // Active nurses - role dependent
        if (in_array($user->role, ['admin', 'superadmin'])) {
            $stats['active_nurses'] = (clone $baseQuery)->where('schedule_date', '>=', today())
                ->distinct('nurse_id')
                ->count();
        } else {
            // For other roles, this metric might not be relevant or should be limited
            $stats['active_nurses'] = $user->role === 'nurse' ? 1 : 0;
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getNurses(): JsonResponse
    {
        $user = auth()->user();

        // If the user is a nurse, only return themselves
        if ($user->role === 'nurse') {
            $nurses = collect([$user->only(['id', 'first_name', 'last_name', 'email'])]);
        } else {
            $query = User::where('role', 'nurse')
                ->where('is_active', true)
                ->where('verification_status', 'verified')
                ->select('id', 'first_name', 'last_name', 'email');

            // For doctors, only return nurses assigned to their care plans
            if ($user->role === 'doctor') {
                $assignedNurseIds = CarePlan::where('doctor_id', $user->id)
                    ->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNotNull('primary_nurse_id')
                        ->orWhereNotNull('secondary_nurse_id');
                    })
                    ->get()
                    ->flatMap(function($carePlan) {
                        return array_filter([$carePlan->primary_nurse_id, $carePlan->secondary_nurse_id]);
                    })
                    ->unique()
                    ->values();

                $query->whereIn('id', $assignedNurseIds);
            }

            $nurses = $query->orderBy('first_name')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $nurses
        ]);
    }

    private function getStatusColor(string $status): string
    {
        return match($status) {
            'scheduled' => '#6b7280',
            'confirmed' => '#3b82f6',
            'in_progress' => '#f59e0b',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
            default => '#6b7280'
        };
    }


    /**
     * Export schedules to CSV
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        
        // Use the same filtering logic as index method
        $query = Schedule::with(['nurse', 'carePlan.patient', 'createdBy']);

        // Apply role-based filtering first
        $this->applyRoleBasedFiltering($query, $user);

        $query->latest('schedule_date');

        // Apply all the same filters as index method
        if ($request->has('status') && $request->status !== 'all') {
            $statuses = is_array($request->status) 
                ? $request->status 
                : explode(',', $request->status);
            
            $query->whereIn('status', $statuses);
        }
        
        if ($request->filled('shift_type') && $request->shift_type !== 'all') {
            $query->where('shift_type', $request->shift_type);
        }

        // Only admins can filter by nurse
        if ($request->filled('nurse_id') && $request->nurse_id !== 'all' && in_array($user->role, ['admin', 'superadmin'])) {
            $query->where('nurse_id', $request->nurse_id);
        }

        if ($request->filled('care_plan_id') && $request->care_plan_id !== 'all') {
            // For doctors, ensure they can only export their own care plans
            if ($user->role === 'doctor') {
                $query->where('care_plan_id', $request->care_plan_id)
                      ->whereHas('carePlan', function($q) use ($user) {
                          $q->where('doctor_id', $user->id);
                      });
            } else {
                $query->where('care_plan_id', $request->care_plan_id);
            }
        }

        // Date range filters
        if ($request->filled('start_date')) {
            $query->where('schedule_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('schedule_date', '<=', $request->end_date);
        }

        // View filters
        if ($request->filled('view')) {
            switch ($request->view) {
                case 'today':
                    $query->where('schedule_date', today());
                    break;
                case 'upcoming':
                    $query->where('schedule_date', '>=', today());
                    break;
                case 'this_week':
                    $query->whereBetween('schedule_date', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('shift_notes', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhereHas('nurse', function($nq) use ($search) {
                    $nq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('carePlan', function($cq) use ($search) {
                    $cq->where('title', 'like', "%{$search}%")
                        ->orWhereHas('patient', function($pq) use ($search) {
                            $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            });
        }

        // Get all matching schedules (no pagination for export)
        $schedules = $query->get();

        // Generate filename with timestamp and role info
        $filename = 'schedules_export_' . $user->role . '_' . now()->format('Y-m-d_H-i-s');
        
        // Add filter info to filename if applied
        $filterParts = [];
        if ($request->filled('status') && $request->status !== 'all') {
            $filterParts[] = 'status-' . $request->status;
        }
        if ($request->filled('nurse_id') && $request->nurse_id !== 'all') {
            $nurse = User::find($request->nurse_id);
            if ($nurse) {
                $filterParts[] = 'nurse-' . str_replace(' ', '_', $nurse->first_name . '_' . $nurse->last_name);
            }
        }
        if ($request->filled('view') && $request->view !== 'all') {
            $filterParts[] = 'view-' . $request->view;
        }
        
        if (!empty($filterParts)) {
            $filename .= '_' . implode('_', $filterParts);
        }
        
        $filename .= '.csv';

        // Set headers for CSV download
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        // Create CSV content
        $callback = function() use ($schedules) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for proper UTF-8 encoding in Excel
            fwrite($file, "\xEF\xBB\xBF");
            
            // CSV Headers
            $headers = [
                'Schedule ID',
                'Nurse Name',
                'Nurse Email',
                'Care Plan',
                'Patient Name',
                'Schedule Date',
                'Start Time',
                'End Time',
                'Duration (mins)',
                'Shift Type',
                'Status',
                'Location',
                'Confirmed At',
                'Actual Start',
                'Actual End',
                'Actual Duration (mins)',
                'Shift Notes',
                'Cancellation Reason',
                'Created By',
                'Created At',
                'Updated At'
            ];
            
            fputcsv($file, $headers);
            
            // Add data rows
            foreach ($schedules as $schedule) {
                $row = [
                    $schedule->id,
                    $schedule->nurse ? $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name : 'N/A',
                    $schedule->nurse ? $schedule->nurse->email : 'N/A',
                    $schedule->carePlan ? $schedule->carePlan->title : 'No Care Plan',
                    $schedule->carePlan && $schedule->carePlan->patient 
                        ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name 
                        : 'N/A',
                    $schedule->schedule_date ? $schedule->schedule_date->format('Y-m-d') : '',
                    $schedule->start_time,
                    $schedule->end_time,
                    $schedule->duration_minutes,
                    $schedule->formatted_shift_type,
                    ucfirst(str_replace('_', ' ', $schedule->status)),
                    $schedule->location ?: '',
                    $schedule->nurse_confirmed_at ? $schedule->nurse_confirmed_at->format('Y-m-d H:i:s') : '',
                    $schedule->actual_start_time ? $schedule->actual_start_time->format('Y-m-d H:i:s') : '',
                    $schedule->actual_end_time ? $schedule->actual_end_time->format('Y-m-d H:i:s') : '',
                    $schedule->actual_duration_minutes ?: '',
                    $schedule->shift_notes ?: '',
                    $schedule->cancellation_reason ?: '',
                    $schedule->createdBy ? $schedule->createdBy->first_name . ' ' . $schedule->createdBy->last_name : 'System',
                    $schedule->created_at ? $schedule->created_at->format('Y-m-d H:i:s') : '',
                    $schedule->updated_at ? $schedule->updated_at->format('Y-m-d H:i:s') : '',
                ];
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        // Log the export action
        \Log::info('Schedules exported', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'count' => $schedules->count(),
            'filters' => $request->only(['status', 'shift_type', 'nurse_id', 'view', 'start_date', 'end_date', 'search']),
            'filename' => $filename
        ]);

        return response()->stream($callback, 200, $headers);
    }
}