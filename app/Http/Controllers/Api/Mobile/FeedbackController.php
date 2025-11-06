<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PatientFeedback;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    /**
     * Get all feedback submitted by the authenticated patient
     * GET /api/mobile/patient/feedback
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            $feedbacks = PatientFeedback::with(['nurse:id,first_name,last_name', 'schedule'])
                ->where('patient_id', $patient->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($feedback) {
                    return [
                        'id' => $feedback->id,
                        'nurse_id' => $feedback->nurse_id,
                        'nurse_name' => $feedback->nurse 
                            ? $feedback->nurse->first_name . ' ' . $feedback->nurse->last_name
                            : 'Unknown Nurse',
                        'schedule_id' => $feedback->schedule_id,
                        'rating' => $feedback->rating,
                        'stars' => $feedback->stars,
                        'feedback_text' => $feedback->feedback_text,
                        'would_recommend' => $feedback->would_recommend,
                        'care_date' => $feedback->care_date,
                        'status' => $feedback->status,
                        'is_responded' => $feedback->is_responded,
                        'response_text' => $feedback->response_text,
                        'responded_at' => $feedback->responded_at?->toIso8601String(),
                        'days_since_submission' => $feedback->days_since_submission,
                        'created_at' => $feedback->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $feedbacks,
                'total' => $feedbacks->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching feedback: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch feedback.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Submit feedback for a nurse
     * POST /api/mobile/patient/feedback
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can submit feedback.'
                ], 403);
            }

            $validated = $request->validate([
                'nurse_id' => 'required|exists:users,id',
                'schedule_id' => 'nullable|exists:schedules,id',
                'rating' => 'required|integer|min:1|max:5',
                'feedback_text' => 'required|string|min:10|max:1000',
                'would_recommend' => 'required|boolean',
                'care_date' => 'nullable|date',
            ]);

            // Verify the nurse exists and is a nurse
            $nurse = User::where('id', $validated['nurse_id'])
                ->where('role', 'nurse')
                ->first();

            if (!$nurse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid nurse selected.'
                ], 422);
            }

            // Verify schedule if provided
            if (isset($validated['schedule_id'])) {
                $schedule = Schedule::where('id', $validated['schedule_id'])
                    ->whereHas('carePlan', function($q) use ($patient) {
                        $q->where('patient_id', $patient->id);
                    })
                    ->where('nurse_id', $validated['nurse_id'])
                    ->where('status', 'completed')
                    ->first();

                if (!$schedule) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid schedule selected or schedule not completed yet.'
                    ], 422);
                }
            }

            // Check if feedback already exists for this patient-nurse combination
            $existingFeedbackQuery = PatientFeedback::where('patient_id', $patient->id)
                ->where('nurse_id', $validated['nurse_id']);
            
            if (isset($validated['schedule_id'])) {
                $existingFeedbackQuery->where('schedule_id', $validated['schedule_id']);
            } else {
                $existingFeedbackQuery->whereNull('schedule_id');
            }
            
            $existingFeedback = $existingFeedbackQuery->first();

            if ($existingFeedback) {
                // Update existing feedback
                $existingFeedback->update([
                    'rating' => $validated['rating'],
                    'feedback_text' => $validated['feedback_text'],
                    'would_recommend' => $validated['would_recommend'],
                    'care_date' => $validated['care_date'] ?? $existingFeedback->care_date,
                ]);

                $feedback = $existingFeedback;
                $message = 'Your feedback has been updated successfully!';
            } else {
                // Create new feedback
                $feedback = PatientFeedback::create([
                    'patient_id' => $patient->id,
                    'nurse_id' => $validated['nurse_id'],
                    'schedule_id' => $validated['schedule_id'] ?? null,
                    'rating' => $validated['rating'],
                    'feedback_text' => $validated['feedback_text'],
                    'would_recommend' => $validated['would_recommend'],
                    'care_date' => $validated['care_date'] ?? now()->toDateString(),
                    'status' => 'pending',
                ]);

                $message = 'Thank you for your feedback!';
            }

            $feedback->load('nurse:id,first_name,last_name');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $feedback->id,
                    'nurse_id' => $feedback->nurse_id,
                    'nurse_name' => $feedback->nurse 
                        ? $feedback->nurse->first_name . ' ' . $feedback->nurse->last_name
                        : 'Unknown Nurse',
                    'schedule_id' => $feedback->schedule_id,
                    'rating' => $feedback->rating,
                    'stars' => $feedback->stars,
                    'feedback_text' => $feedback->feedback_text,
                    'would_recommend' => $feedback->would_recommend,
                    'care_date' => $feedback->care_date,
                    'status' => $feedback->status,
                    'created_at' => $feedback->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error submitting feedback: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get nurses that the patient can provide feedback for
     * GET /api/mobile/patient/feedback/nurses
     */
    public function getNursesForFeedback(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            // Get nurses who have completed schedules with this patient
            $nurses = User::where('role', 'nurse')
                ->whereHas('nurseSchedules', function ($query) use ($patient) {
                    $query->whereHas('carePlan', function($q) use ($patient) {
                        $q->where('patient_id', $patient->id);
                    })
                    ->where('status', 'completed');
                })
                ->select('id', 'first_name', 'last_name', 'phone')
                ->get()
                ->map(function ($nurse) use ($patient) {
                    // Get recent completed schedules with this nurse
                    $recentSchedules = Schedule::whereHas('carePlan', function($q) use ($patient) {
                            $q->where('patient_id', $patient->id);
                        })
                        ->where('nurse_id', $nurse->id)
                        ->where('status', 'completed')
                        ->orderBy('schedule_date', 'desc')
                        ->orderBy('end_time', 'desc')
                        ->take(5)
                        ->get();

                    // Check for general feedback (not schedule-specific)
                    $existingFeedback = PatientFeedback::where('patient_id', $patient->id)
                        ->where('nurse_id', $nurse->id)
                        ->whereNull('schedule_id')
                        ->first();

                    // Get nurse statistics
                    $nurseStats = PatientFeedback::nurseStatistics($nurse->id);

                    return [
                        'id' => $nurse->id,
                        'name' => $nurse->first_name . ' ' . $nurse->last_name,
                        'phone' => $nurse->phone,
                        'has_general_feedback' => $existingFeedback !== null,
                        'existing_rating' => $existingFeedback?->rating,
                        'completed_schedules' => $recentSchedules->count(),
                        'last_schedule_date' => $recentSchedules->first()?->schedule_date?->toDateString(),
                        'average_rating' => $nurseStats['average_rating'] ?? null,
                        'total_feedback' => $nurseStats['total_feedback'] ?? 0,
                        'recent_schedules' => $recentSchedules->map(function($schedule) use ($patient, $nurse) {
                            $hasFeedback = PatientFeedback::where('patient_id', $patient->id)
                                ->where('nurse_id', $nurse->id)
                                ->where('schedule_id', $schedule->id)
                                ->exists();
                            
                            return [
                                'id' => $schedule->id,
                                'date' => $schedule->schedule_date->toDateString(),
                                'time' => $schedule->start_time . ' - ' . $schedule->end_time,
                                'location' => $schedule->location,
                                'has_feedback' => $hasFeedback,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $nurses->values(),
                'total' => $nurses->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching nurses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nurses.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get feedback statistics for the patient
     * GET /api/mobile/patient/feedback/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            $feedbacks = PatientFeedback::where('patient_id', $patient->id)->get();

            $stats = [
                'total_feedback_submitted' => $feedbacks->count(),
                'average_rating_given' => round($feedbacks->avg('rating'), 2),
                'nurses_rated' => $feedbacks->pluck('nurse_id')->unique()->count(),
                'would_recommend_count' => $feedbacks->where('would_recommend', true)->count(),
                'pending_responses' => $feedbacks->where('status', 'pending')->count(),
                'responded_feedback' => $feedbacks->where('status', 'responded')->count(),
                'rating_distribution' => [
                    '5_star' => $feedbacks->where('rating', 5)->count(),
                    '4_star' => $feedbacks->where('rating', 4)->count(),
                    '3_star' => $feedbacks->where('rating', 3)->count(),
                    '2_star' => $feedbacks->where('rating', 2)->count(),
                    '1_star' => $feedbacks->where('rating', 1)->count(),
                ],
                'recent_feedback' => $feedbacks->sortByDesc('created_at')->take(3)->map(function ($feedback) {
                    return [
                        'id' => $feedback->id,
                        'nurse_name' => $feedback->nurse 
                            ? $feedback->nurse->first_name . ' ' . $feedback->nurse->last_name
                            : 'Unknown Nurse',
                        'rating' => $feedback->rating,
                        'created_at' => $feedback->created_at->toIso8601String(),
                    ];
                })->values(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}