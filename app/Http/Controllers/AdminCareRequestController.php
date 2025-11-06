<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CareRequest;
use App\Models\CarePayment;
use App\Models\CareFeeStructure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminCareRequestController extends Controller
{
    /**
     * Get all care requests with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $status = $request->query('status');
            $search = $request->query('search');

            $query = CareRequest::with([
                'patient:id,first_name,last_name,phone,email',
                'assignedNurse:id,first_name,last_name,phone',
                'assessmentPayment',
                'carePayment'
            ])->recent();

            if ($status) {
                $query->byStatus($status);
            }

            if ($search) {
                $query->whereHas('patient', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $requests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $requests->items(),
                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care requests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch care requests.',
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'pending_payment' => CareRequest::where('status', 'pending_payment')->count(),
                'payment_received' => CareRequest::where('status', 'payment_received')->count(),
                'nurse_assigned' => CareRequest::where('status', 'nurse_assigned')->count(),
                'assessment_scheduled' => CareRequest::where('status', 'assessment_scheduled')->count(),
                'assessment_completed' => CareRequest::where('status', 'assessment_completed')->count(),
                'under_review' => CareRequest::where('status', 'under_review')->count(),
                'awaiting_care_payment' => CareRequest::where('status', 'awaiting_care_payment')->count(),
                'care_active' => CareRequest::where('status', 'care_active')->count(),
                'total_requests' => CareRequest::count(),
                'total_revenue' => CarePayment::where('status', 'completed')->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics.',
            ], 500);
        }
    }

    /**
     * Assign nurse to care request
     */
    public function assignNurse($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nurse_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $careRequest = CareRequest::find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            // Verify nurse exists and has correct role
            $nurse = User::find($request->nurse_id);
            if (!$nurse || !in_array($nurse->role, ['nurse', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid nurse selected.'
                ], 422);
            }

            // Verify payment has been completed
            if (!$careRequest->hasCompletedAssessmentPayment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assessment payment not completed yet.'
                ], 400);
            }

            $careRequest->assignNurse($request->nurse_id);

            // TODO: Send notification to nurse and patient

            return response()->json([
                'success' => true,
                'message' => 'Nurse assigned successfully.',
                'data' => $careRequest->load('assignedNurse')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error assigning nurse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign nurse.',
            ], 500);
        }
    }

    /**
     * Schedule assessment
     */
    public function scheduleAssessment($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'scheduled_at' => 'required|date|after:now',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $careRequest = CareRequest::find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            if (!$careRequest->assigned_nurse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nurse must be assigned first.'
                ], 400);
            }

            $careRequest->scheduleAssessment($request->scheduled_at);

            // TODO: Send notification to nurse and patient

            return response()->json([
                'success' => true,
                'message' => 'Assessment scheduled successfully.',
                'data' => $careRequest
            ]);

        } catch (\Exception $e) {
            \Log::error('Error scheduling assessment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule assessment.',
            ], 500);
        }
    }

    /**
     * Mark assessment as completed (called after nurse creates assessment)
     */
    public function completeAssessment($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'assessment_id' => 'required|exists:medical_assessments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $careRequest = CareRequest::find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            $careRequest->completeAssessment($request->assessment_id);
            $careRequest->markUnderReview(); // Automatically move to review

            return response()->json([
                'success' => true,
                'message' => 'Assessment marked as completed and moved to review.',
                'data' => $careRequest
            ]);

        } catch (\Exception $e) {
            \Log::error('Error completing assessment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete assessment.',
            ], 500);
        }
    }

    /**
     * Issue care cost to patient (after reviewing assessment)
     */
    public function issueCareCost($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'care_plan_details' => 'required|string',
                'duration_days' => 'nullable|integer|min:1',
                'sessions_per_week' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $careRequest = CareRequest::find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            if ($careRequest->status !== 'under_review' && $careRequest->status !== 'assessment_completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request must be under review to issue care cost.'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Get fee structure for tax calculation
                $feeStructure = CareFeeStructure::active()->first();
                $taxPercentage = $feeStructure ? $feeStructure->tax_percentage : 0;
                
                $amount = $request->amount;
                $taxAmount = $amount * ($taxPercentage / 100);
                $totalAmount = $amount + $taxAmount;

                // Create care payment
                $payment = CarePayment::create([
                    'care_request_id' => $careRequest->id,
                    'patient_id' => $careRequest->patient_id,
                    'payment_type' => 'care_fee',
                    'amount' => $amount,
                    'currency' => 'GHS',
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'description' => 'Home Care Services - ' . $request->care_plan_details,
                    'expires_at' => now()->addDays(7), // 7 days to pay
                    'metadata' => [
                        'care_plan_details' => $request->care_plan_details,
                        'duration_days' => $request->duration_days,
                        'sessions_per_week' => $request->sessions_per_week,
                        'issued_at' => now()->toIso8601String(),
                        'issued_by' => auth()->id(),
                    ]
                ]);

                // Update care request status
                $careRequest->update([
                    'status' => 'awaiting_care_payment',
                    'admin_notes' => $request->care_plan_details,
                ]);

                DB::commit();

                // TODO: Send notification to patient with payment details

                return response()->json([
                    'success' => true,
                    'message' => 'Care cost issued successfully.',
                    'data' => [
                        'care_request' => $careRequest,
                        'payment' => $payment,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error issuing care cost: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to issue care cost.',
            ], 500);
        }
    }

    /**
     * Start care (after care payment completed)
     */
    public function startCare($id): JsonResponse
    {
        try {
            $careRequest = CareRequest::find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            if (!$careRequest->hasCompletedCarePayment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care payment not completed yet.'
                ], 400);
            }

            $careRequest->startCare();

            // TODO: Create care plan schedules, send notifications

            return response()->json([
                'success' => true,
                'message' => 'Care started successfully.',
                'data' => $careRequest
            ]);

        } catch (\Exception $e) {
            \Log::error('Error starting care: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start care.',
            ], 500);
        }
    }

    /**
     * Reject care request
     */
    public function reject($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|min:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $careRequest = CareRequest::find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            $careRequest->reject($request->reason);

            // TODO: Send notification to patient, process refund if applicable

            return response()->json([
                'success' => true,
                'message' => 'Care request rejected.',
                'data' => $careRequest
            ]);

        } catch (\Exception $e) {
            \Log::error('Error rejecting care request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject care request.',
            ], 500);
        }
    }

    /**
     * Get available nurses for assignment
     */
    public function getAvailableNurses(Request $request): JsonResponse
    {
        try {
            $careType = $request->query('care_type');
            $region = $request->query('region');

            $query = User::where('role', 'nurse')
                ->where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'phone', 'email');

            // TODO: Add logic to filter by specialization, region, availability

            $nurses = $query->get();

            return response()->json([
                'success' => true,
                'data' => $nurses
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching nurses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nurses.',
            ], 500);
        }
    }
}