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
use Illuminate\Validation\Rule;

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
     * Assign nurse to care request and schedule assessment
     */
    public function assignNurse($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nurse_id' => 'required|exists:users,id',
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

            DB::beginTransaction();

            try {
                // Assign nurse
                $careRequest->update([
                    'assigned_nurse_id' => $request->nurse_id,
                    'assessment_scheduled_at' => $request->scheduled_at,
                    'status' => 'assessment_scheduled'
                ]);

                DB::commit();

                // TODO: Send notification to nurse and patient about assignment and scheduled assessment

                return response()->json([
                    'success' => true,
                    'message' => 'Nurse assigned and assessment scheduled successfully.',
                    'data' => $careRequest->load('assignedNurse')
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

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
 * Create or update payment record
 */
protected function createOrUpdatePayment(
    CareRequest $careRequest,
    string $paymentType,
    string $paymentMethod,
    User $patient,
    ?float $customAmount = null
): void
{
    try {
        $payment = CarePayment::where('care_request_id', $careRequest->id)
            ->where('payment_type', $paymentType)
            ->first();

        // REMOVE THIS EARLY RETURN - Allow updates even for completed payments
        // if ($payment && $payment->status === 'completed') {
        //     return;
        // }

        // Calculate amount
        if ($customAmount !== null && $paymentType === 'care_fee') {
            // Use the exact amount provided by admin (no tax calculation)
            $amount = $customAmount;
            $taxAmount = 0;
            $totalAmount = $customAmount;
            $currency = 'GHS';
        } else {
            // Use fee structure for assessment
            $feeStructure = null;
            if ($paymentType === 'assessment_fee') {
                $feeStructure = CareFeeStructure::getAssessmentFee($careRequest->care_type, $careRequest->region);
            }

            $amount = 100.00; // Default
            $taxAmount = 0;
            $totalAmount = 100.00;
            $currency = 'GHS';

            if ($feeStructure) {
                $amount = $feeStructure->getAmountForRegion($careRequest->region);
                $taxPercentage = $feeStructure->tax_percentage ?? 0;
                $taxAmount = $amount * ($taxPercentage / 100);
                $totalAmount = $amount + $taxAmount;
                $currency = $feeStructure->currency;
            }
        }

        $transactionId = $payment ? $payment->transaction_id : $this->generateInternalTransactionId($paymentType === 'assessment_fee' ? 'ASSESS' : 'CARE');

        $paymentData = [
            'status' => 'completed',
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'paid_at' => $payment ? $payment->paid_at : now(), // Preserve original paid_at if exists
            'expires_at' => null,
            'metadata' => array_merge($payment->metadata ?? [], [
                'payment_method' => $paymentMethod,
                'is_admin_created' => true,
                'created_by_admin' => auth()->id(),
                'admin_name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                'updated_at' => now()->toIso8601String(),
                'note' => $payment ? 'Payment updated by admin during status change' : 'Payment recorded by admin during status change',
                'custom_amount' => $customAmount !== null ? true : false,
                'amount_history' => $this->buildAmountHistory($payment, $customAmount) // Track amount changes
            ])
        ];

        if ($payment) {
            // Update existing payment
            $updateData = $paymentData;
            
            // Always update amount if custom amount is provided
            if ($customAmount !== null) {
                $updateData['amount'] = $amount;
                $updateData['tax_amount'] = $taxAmount;
                $updateData['total_amount'] = $totalAmount;
            }
            
            $payment->update($updateData);
            
            \Log::info('Payment updated during status change', [
                'payment_id' => $payment->id,
                'care_request_id' => $careRequest->id,
                'payment_type' => $paymentType,
                'old_amount' => $payment->total_amount,
                'new_amount' => $totalAmount,
                'custom_amount' => $customAmount
            ]);
        } else {
            // Create new payment
            $paymentData = array_merge($paymentData, [
                'care_request_id' => $careRequest->id,
                'patient_id' => $patient->id,
                'payment_type' => $paymentType,
                'amount' => $amount,
                'currency' => $currency,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_provider' => 'paystack',
                'description' => $paymentType === 'assessment_fee' ? 'Home Care Assessment Fee' : 'Home Care Service Fee',
            ]);

            $payment = CarePayment::create($paymentData);
            
            \Log::info('Payment created during status change', [
                'payment_id' => $payment->id,
                'care_request_id' => $careRequest->id,
                'payment_type' => $paymentType,
                'amount' => $totalAmount,
                'custom_amount' => $customAmount
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Failed to create/update payment during status change', [
            'care_request_id' => $careRequest->id,
            'payment_type' => $paymentType,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * Build amount history for tracking changes
 */
protected function buildAmountHistory($payment, $newAmount): array
{
    $history = [];
    
    if ($payment && isset($payment->metadata['amount_history'])) {
        $history = $payment->metadata['amount_history'];
    }
    
    if ($payment && $newAmount !== null) {
        $history[] = [
            'previous_amount' => $payment->total_amount,
            'new_amount' => $newAmount,
            'changed_by' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'changed_at' => now()->toIso8601String()
        ];
    }
    
    return $history;
}
    /**
     * Get all patients for care request creation
     */
    public function getPatients(): JsonResponse
    {
        try {
            $patients = User::where('role', 'patient')
                ->where('is_active', true)
                ->where('is_verified',true)
                ->select('id', 'first_name', 'last_name', 'phone', 'email')
                ->orderBy('first_name')
                ->get();
            \Log::info("total");
            \Log::info($patients->count());
            return response()->json([
                'success' => true,
                'data' => $patients
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patients: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patients.',
            ], 500);
        }
    }


    /**
     * Export care requests to CSV
     * GET /api/admin/care-requests/export
     */
    public function export(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Use the same filtering logic as index method
            $query = CareRequest::with([
                'patient:id,first_name,last_name,phone,email',
                'assignedNurse:id,first_name,last_name,phone',
                'assessmentPayment',
                'carePayment'
            ])->latest('created_at');

            // Apply status filter
            if ($request->filled('status') && $request->status !== 'all') {
                $statuses = is_array($request->status) 
                    ? $request->status 
                    : explode(',', $request->status);
                
                $query->whereIn('status', $statuses);
            }

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('patient', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Date range filters
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', $request->end_date);
            }

            // Care type filter
            if ($request->filled('care_type') && $request->care_type !== 'all') {
                $query->where('care_type', $request->care_type);
            }

            // Urgency level filter
            if ($request->filled('urgency_level') && $request->urgency_level !== 'all') {
                $query->where('urgency_level', $request->urgency_level);
            }

            // Get all matching care requests (no pagination for export)
            $careRequests = $query->get();

            // Generate filename with timestamp
            $filename = 'care_requests_export_' . now()->format('Y-m-d_H-i-s');
            
            // Add filter info to filename if applied
            $filterParts = [];
            if ($request->filled('status') && $request->status !== 'all') {
                $filterParts[] = 'status-' . (is_array($request->status) ? implode('-', $request->status) : $request->status);
            }
            if ($request->filled('care_type') && $request->care_type !== 'all') {
                $filterParts[] = 'type-' . $request->care_type;
            }
            if ($request->filled('urgency_level') && $request->urgency_level !== 'all') {
                $filterParts[] = 'urgency-' . $request->urgency_level;
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
            $callback = function() use ($careRequests) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for proper UTF-8 encoding in Excel
                fwrite($file, "\xEF\xBB\xBF");
                
                // CSV Headers
                $headers = [
                    'Request ID',
                    'Patient Name',
                    'Patient Phone',
                    'Patient Email',
                    'Care Type',
                    'Urgency Level',
                    'Status',
                    'Description',
                    'Special Requirements',
                    'Preferred Language',
                    'Preferred Start Date',
                    'Preferred Time',
                    'Service Address',
                    'City',
                    'Region',
                    'Assigned Nurse',
                    'Nurse Phone',
                    'Assessment Fee Status',
                    'Assessment Fee Amount',
                    'Assessment Paid At',
                    'Care Fee Status',
                    'Care Fee Amount',
                    'Care Paid At',
                    'Total Revenue',
                    'Assessment Scheduled At',
                    'Assessment Completed At',
                    'Care Started At',
                    'Care Ended At',
                    'Cancelled At',
                    'Rejection Reason',
                    'Admin Notes',
                    'Created At',
                    'Updated At',
                ];
                
                fputcsv($file, $headers);
                
                // Add data rows
                foreach ($careRequests as $request) {
                    $assessmentPayment = $request->assessmentPayment;
                    $carePayment = $request->carePayment;
                    
                    $totalRevenue = 0;
                    if ($assessmentPayment && $assessmentPayment->status === 'completed') {
                        $totalRevenue += $assessmentPayment->total_amount;
                    }
                    if ($carePayment && $carePayment->status === 'completed') {
                        $totalRevenue += $carePayment->total_amount;
                    }
                    
                    $row = [
                        $request->id,
                        $request->patient ? $request->patient->first_name . ' ' . $request->patient->last_name : 'N/A',
                        $request->patient ? $request->patient->phone : 'N/A',
                        $request->patient ? $request->patient->email : 'N/A',
                        ucwords(str_replace('_', ' ', $request->care_type)),
                        ucwords($request->urgency_level),
                        $request->formatted_status,
                        $request->description ?: '',
                        $request->special_requirements ?: '',
                        $request->preferred_language ?: '',
                        $request->preferred_start_date ? $request->preferred_start_date->format('Y-m-d') : '',
                        $request->preferred_time ? ucwords($request->preferred_time) : '',
                        $request->service_address ?: '',
                        $request->city ?: '',
                        $request->region ?: '',
                        $request->assignedNurse ? $request->assignedNurse->first_name . ' ' . $request->assignedNurse->last_name : 'Not Assigned',
                        $request->assignedNurse ? $request->assignedNurse->phone : '',
                        $assessmentPayment ? ucfirst($assessmentPayment->status) : 'Not Created',
                        $assessmentPayment ? $assessmentPayment->currency . ' ' . number_format($assessmentPayment->total_amount, 2) : '',
                        $assessmentPayment && $assessmentPayment->paid_at ? $assessmentPayment->paid_at->format('Y-m-d H:i:s') : '',
                        $carePayment ? ucfirst($carePayment->status) : 'Not Created',
                        $carePayment ? $carePayment->currency . ' ' . number_format($carePayment->total_amount, 2) : '',
                        $carePayment && $carePayment->paid_at ? $carePayment->paid_at->format('Y-m-d H:i:s') : '',
                        $totalRevenue > 0 ? 'GHS ' . number_format($totalRevenue, 2) : '',
                        $request->assessment_scheduled_at ? $request->assessment_scheduled_at->format('Y-m-d H:i:s') : '',
                        $request->assessment_completed_at ? $request->assessment_completed_at->format('Y-m-d H:i:s') : '',
                        $request->care_started_at ? $request->care_started_at->format('Y-m-d H:i:s') : '',
                        $request->care_ended_at ? $request->care_ended_at->format('Y-m-d H:i:s') : '',
                        $request->cancelled_at ? $request->cancelled_at->format('Y-m-d H:i:s') : '',
                        $request->rejection_reason ?: '',
                        $request->admin_notes ?: '',
                        $request->created_at ? $request->created_at->format('Y-m-d H:i:s') : '',
                        $request->updated_at ? $request->updated_at->format('Y-m-d H:i:s') : '',
                    ];
                    
                    fputcsv($file, $row);
                }
                
                fclose($file);
            };

            // Log the export action
            \Log::info('Care requests exported', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'count' => $careRequests->count(),
                'filters' => $request->only(['status', 'search', 'start_date', 'end_date', 'care_type', 'urgency_level']),
                'filename' => $filename
            ]);

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error exporting care requests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to export care requests.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a new care request (admin-initiated)
     */
    public function createRequest(Request $request): JsonResponse
    {
        try {
            // Determine if payment method is required based on status
            $statusesRequiringPaymentMethod = [
                'payment_received',
                'nurse_assigned',
                'assessment_scheduled',
                'assessment_completed',
                'under_review',
                'care_payment_received',
                'care_active'
            ];
            
            $requiresPaymentMethod = in_array($request->initial_status, $statusesRequiringPaymentMethod);

            $validator = Validator::make(
                $request->all(),
                [
                    'patient_id' => 'required|exists:users,id',
                    'initial_status' => 'required|in:pending_payment,payment_received,nurse_assigned,assessment_scheduled,assessment_completed,under_review,care_plan_created,awaiting_care_payment,care_payment_received,care_active',
                    'payment_method' => [
                        $requiresPaymentMethod ? 'required' : 'nullable',
                        'in:mobile_money,card,bank_transfer,cash,insurance'
                    ],
                    'assessment_scheduled_at' => [
                        'nullable',
                        'date',
                        'after:now',
                        Rule::requiredIf($request->initial_status === 'assessment_scheduled')
                    ],
                    'care_type' => 'required|in:general_nursing,elderly_care,post_surgical,chronic_disease,palliative_care,rehabilitation,wound_care,medication_management',
                    'urgency_level' => 'required|in:routine,urgent,emergency',
                    'description' => 'required|string|min:20|max:1000',
                    'special_requirements' => 'nullable|string|max:500',
                    'preferred_language' => 'nullable|string|max:50',
                    'preferred_start_date' => 'nullable|date|after_or_equal:today',
                    'preferred_time' => 'nullable|in:morning,afternoon,evening,night,anytime',
                    'service_address' => 'required|string|max:500',
                    'city' => 'nullable|string|max:100',
                    'region' => 'nullable|string|max:100',
                    'latitude' => 'nullable|numeric|between:-90,90',
                    'longitude' => 'nullable|numeric|between:-180,180',
                    'admin_notes' => 'nullable|string|max:1000',
                ],
                [
                    'assessment_scheduled_at.required' => 'Assessment date and time is required when status is assessment_scheduled.',
                    'assessment_scheduled_at.after' => 'Assessment must be scheduled in the future.',
                    'payment_method.required' => 'Payment method is required for this status.',
                    'payment_method.in' => 'Invalid payment method selected.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patient = User::find($request->patient_id);
            if (!$patient || $patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient selected.'
                ], 422);
            }

            DB::beginTransaction();

            try {
                $careRequest = CareRequest::create([
                    'patient_id' => $request->patient_id,
                    'care_type' => $request->care_type,
                    'urgency_level' => $request->urgency_level,
                    'description' => $request->description,
                    'special_requirements' => $request->special_requirements,
                    'preferred_language' => $request->preferred_language,
                    'preferred_start_date' => $request->preferred_start_date,
                    'preferred_time' => $request->preferred_time,
                    'service_address' => $request->service_address,
                    'city' => $request->city,
                    'region' => $request->region,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'status' => $request->initial_status,
                    'assessment_scheduled_at' => $request->assessment_scheduled_at,
                    'admin_notes' => $this->buildAdminNotes($request)
                ]);

                // Pass payment method to payment creation
                $paymentResults = $this->createPaymentRecords(
                    $careRequest, 
                    $request->initial_status, 
                    $request->region, 
                    $patient,
                    $request->payment_method // ADD THIS PARAMETER
                );

                if (!empty($paymentResults['errors'])) {
                    \Log::warning('Payment creation warnings', [
                        'care_request_id' => $careRequest->id,
                        'errors' => $paymentResults['errors']
                    ]);
                }

                DB::commit();

                $careRequest->refresh();
                $careRequest->load(['patient', 'assessmentPayment', 'carePayment', 'assignedNurse']);

                \Log::info('Care request created by admin', [
                    'care_request_id' => $careRequest->id,
                    'patient_id' => $patient->id,
                    'initial_status' => $request->initial_status,
                    'payment_method' => $request->payment_method,
                    'admin_id' => auth()->id(),
                    'payments_created' => count($paymentResults['payments']),
                ]);

                try {
                    $primaryPayment = $careRequest->assessmentPayment ?? $careRequest->carePayment;
                    
                    $patient->notify(new \App\Notifications\CareRequestStatusNotification(
                        $careRequest,
                        $primaryPayment,
                        $request->initial_status,
                        true
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send care request notification', [
                        'care_request_id' => $careRequest->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $message = 'Care request created successfully. Patient has been notified.';
                if (!empty($paymentResults['errors'])) {
                    $message .= ' Note: ' . implode(' ', $paymentResults['errors']);
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'care_request' => $careRequest,
                        'payments_info' => [
                            'assessment_payment' => $careRequest->assessmentPayment,
                            'care_payment' => $careRequest->carePayment,
                            'created_count' => count($paymentResults['payments'])
                        ]
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error creating care request: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create care request.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /**
     * Manually update care request status
     */
    public function updateStatus($id, Request $request): JsonResponse
    {
        \Log::info("Update Care Status");
        \Log::info($request->all());
        try {
            $statusesRequiringPaymentMethod = [
                'payment_received',
                'nurse_assigned',
                'assessment_scheduled',
                'assessment_completed',
                'under_review',
                'care_payment_received',
                'care_active'
            ];
            
            $carePaymentStatuses = ['care_payment_received', 'care_active'];
            
            $requiresPaymentMethod = in_array($request->new_status, $statusesRequiringPaymentMethod);
            $requiresCareAmount = in_array($request->new_status, $carePaymentStatuses);

            $validator = Validator::make($request->all(), [
                'new_status' => [
                    'required',
                    'string',
                    Rule::in([
                        'pending_payment',
                        'payment_received',
                        'nurse_assigned',
                        'assessment_scheduled',
                        'assessment_completed',
                        'under_review',
                        'care_plan_created',
                        'awaiting_care_payment',
                        'care_payment_received',
                        'care_active',
                        'care_completed',
                        'cancelled',
                        'rejected'
                    ])
                ],
                'payment_method' => [
                    $requiresPaymentMethod ? 'required' : 'nullable',
                    'in:mobile_money,card,bank_transfer,cash,insurance'
                ],
                'care_amount' => [
                    $requiresCareAmount ? 'required' : 'nullable',
                    'numeric',
                    'min:0.01'
                ],
                'assessment_scheduled_at' => [
                    'nullable',
                    'date',
                    'after:now',
                    Rule::requiredIf($request->new_status === 'assessment_scheduled')
                ],
                'reason' => 'required|string|min:10|max:1000',
            ], [
                'payment_method.required' => 'Payment method is required for this status.',
                'care_amount.required' => 'Care service fee amount is required for this status.',
                'care_amount.min' => 'Care service fee must be greater than 0.',
                'assessment_scheduled_at.required' => 'Assessment date and time is required when status is assessment_scheduled.',
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

            $oldStatus = $careRequest->status;
            $newStatus = $request->new_status;

            DB::beginTransaction();

            try {
                $updateData = [
                    'status' => $newStatus,
                    'admin_notes' => ($careRequest->admin_notes ? $careRequest->admin_notes . "\n\n" : '') .
                        "[" . now()->format('Y-m-d H:i:s') . "] Status changed from '{$oldStatus}' to '{$newStatus}' by " . auth()->user()->first_name . " " . auth()->user()->last_name . "\n" .
                        "Reason: " . $request->reason
                ];

                if ($request->assessment_scheduled_at) {
                    $updateData['assessment_scheduled_at'] = $request->assessment_scheduled_at;
                    $updateData['admin_notes'] .= "\nAssessment Scheduled: " . $request->assessment_scheduled_at;
                }

                if ($request->care_amount) {
                    $updateData['admin_notes'] .= "\nCare Fee: GHS " . number_format($request->care_amount, 2);
                }

                $careRequest->update($updateData);

                switch ($newStatus) {
                    case 'cancelled':
                        if (!$careRequest->cancelled_at) {
                            $careRequest->update(['cancelled_at' => now()]);
                        }
                        break;
                    
                    case 'assessment_completed':
                        if (!$careRequest->assessment_completed_at) {
                            $careRequest->update(['assessment_completed_at' => now()]);
                        }
                        break;
                    
                    case 'care_active':
                        if (!$careRequest->care_started_at) {
                            $careRequest->update(['care_started_at' => now()]);
                        }
                        break;
                    
                    case 'care_completed':
                        if (!$careRequest->care_ended_at) {
                            $careRequest->update(['care_ended_at' => now()]);
                        }
                        break;
                }

                // Create or update payment records if needed
                if ($requiresPaymentMethod && $request->payment_method) {
                    $this->handlePaymentForStatusChange(
                        $careRequest,
                        $newStatus,
                        $oldStatus,
                        $request->payment_method,
                        $request->care_amount // PASS CARE AMOUNT
                    );
                }

                DB::commit();

                $careRequest->refresh();
                $careRequest->load([
                    'patient:id,first_name,last_name,phone,email',
                    'assignedNurse:id,first_name,last_name,phone',
                    'assessmentPayment',
                    'carePayment'
                ]);

                try {
                    $primaryPayment = $careRequest->assessmentPayment ?? $careRequest->carePayment;
                    
                    $careRequest->patient->notify(new \App\Notifications\CareRequestStatusNotification(
                        $careRequest,
                        $primaryPayment,
                        $newStatus,
                        true
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send status change notification', [
                        'care_request_id' => $careRequest->id,
                        'error' => $e->getMessage()
                    ]);
                }

                \Log::info('Care request status manually updated', [
                    'care_request_id' => $id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'payment_method' => $request->payment_method,
                    'care_amount' => $request->care_amount,
                    'admin_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Status updated successfully.',
                    'data' => $careRequest
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error updating care request status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


protected function handlePaymentForStatusChange(
    CareRequest $careRequest,
    string $newStatus,
    string $oldStatus,
    string $paymentMethod,
    ?float $careAmount = null
): void
{
    $assessmentStatuses = [
        'payment_received',
        'nurse_assigned',
        'assessment_scheduled',
        'assessment_completed',
        'under_review'
    ];
    
    $carePaymentStatuses = [
        'care_payment_received',
        'care_active'
    ];

    // Handle assessment payment
    if (in_array($newStatus, $assessmentStatuses)) {
        $existingPayment = $careRequest->assessmentPayment;
        
        if (!$existingPayment || $existingPayment->status !== 'completed') {
            $this->createOrUpdatePayment(
                $careRequest,
                'assessment_fee',
                $paymentMethod,
                $careRequest->patient,
                null
            );
        }
    }

    // Handle care payment
    if (in_array($newStatus, $carePaymentStatuses)) {
        // Ensure assessment payment exists first
        if (!$careRequest->assessmentPayment || $careRequest->assessmentPayment->status !== 'completed') {
            $this->createOrUpdatePayment(
                $careRequest,
                'assessment_fee',
                $paymentMethod,
                $careRequest->patient,
                null
            );
        }
        
        // **FIX: Always update care payment if custom amount is provided**
        $existingCarePayment = $careRequest->carePayment;
        
        // Update payment if:
        // 1. Payment doesn't exist OR isn't completed, OR
        // 2. Custom amount is provided (allows updating completed payments)
        if (!$existingCarePayment || 
            $existingCarePayment->status !== 'completed' || 
            $careAmount !== null) {
            
            $this->createOrUpdatePayment(
                $careRequest,
                'care_fee',
                $paymentMethod,
                $careRequest->patient,
                $careAmount
            );
        } else {
            \Log::info('Care payment already exists and is completed', [
                'care_request_id' => $careRequest->id,
                'payment_id' => $existingCarePayment->id
            ]);
        }
    }
}

    /**
     * Build admin notes with proper formatting
     */
    protected function buildAdminNotes(Request $request): string
    {
        $notes = "[" . now()->format('Y-m-d H:i:s') . "] Created by admin: " . 
                auth()->user()->first_name . " " . auth()->user()->last_name . "\n";
        
        $notes .= "Initial Status: " . $request->initial_status;
        
        if ($request->assessment_scheduled_at) {
            $notes .= "\nAssessment Scheduled: " . $request->assessment_scheduled_at;
        }
        
        if ($request->admin_notes) {
            $notes .= "\n\n" . $request->admin_notes;
        }
        
        return $notes;
    }

    /**
     * Create payment records based on initial status
     * Returns array with 'payments' and 'errors' keys
     */
    protected function createPaymentRecords(
        CareRequest $careRequest, 
        string $status, 
        ?string $region, 
        User $patient,
        ?string $paymentMethod = null // ADD THIS PARAMETER
    ): array
    {
        $payments = [];
        $errors = [];
        
        $assessmentStatuses = [
            'pending_payment',
            'payment_received',
            'nurse_assigned',
            'assessment_scheduled',
            'assessment_completed',
            'under_review'
        ];
        
        $completedAssessmentStatuses = [
            'payment_received',
            'nurse_assigned',
            'assessment_scheduled',
            'assessment_completed',
            'under_review'
        ];
        
        $carePaymentStatuses = [
            'care_plan_created',
            'awaiting_care_payment',
            'care_payment_received',
            'care_active'
        ];
        
        $completedCareStatuses = [
            'care_payment_received',
            'care_active'
        ];
        
        if (in_array($status, $assessmentStatuses)) {
            $markAsCompleted = in_array($status, $completedAssessmentStatuses);
            
            $assessmentPayment = $this->createAssessmentPayment(
                $careRequest,
                $patient,
                $region,
                $markAsCompleted,
                $paymentMethod // PASS PAYMENT METHOD
            );
            
            if ($assessmentPayment) {
                $payments[] = $assessmentPayment;
            } else {
                $errors[] = 'Failed to create assessment payment.';
            }
        }
        
        if (in_array($status, $carePaymentStatuses)) {
            if (!$careRequest->assessmentPayment) {
                $assessmentPayment = $this->createAssessmentPayment(
                    $careRequest, 
                    $patient, 
                    $region, 
                    true,
                    $paymentMethod
                );
                if ($assessmentPayment) {
                    $payments[] = $assessmentPayment;
                }
            }
            
            $markAsCompleted = in_array($status, $completedCareStatuses);
            
            $carePayment = $this->createCarePayment(
                $careRequest,
                $patient,
                $region,
                $markAsCompleted,
                $paymentMethod // PASS PAYMENT METHOD
            );
            
            if ($carePayment) {
                $payments[] = $carePayment;
            } else {
                $errors[] = 'Failed to create care payment.';
            }
        }
        
        return [
            'payments' => $payments,
            'errors' => $errors
        ];
    }

    /**
     * Create assessment payment record
     */
    protected function createAssessmentPayment(
        CareRequest $careRequest, 
        User $patient, 
        ?string $region, 
        bool $markAsCompleted = false,
        ?string $paymentMethod = null // ADD THIS PARAMETER
    ): ?CarePayment
    {
        try {
            $assessmentFee = CareFeeStructure::getAssessmentFee($careRequest->care_type, $region);
            
            if (!$assessmentFee) {
                $amount = 100.00;
                $taxAmount = $amount * 0.15;
                $totalAmount = $amount + $taxAmount;
                $currency = 'GHS';
            } else {
                $amount = $assessmentFee->getAmountForRegion($region);
                $taxAmount = $amount * ($assessmentFee->tax_percentage / 100);
                $totalAmount = $amount + $taxAmount;
                $currency = $assessmentFee->currency;
            }
            
            $paymentData = [
                'care_request_id' => $careRequest->id,
                'patient_id' => $patient->id,
                'payment_type' => 'assessment_fee',
                'amount' => $amount,
                'currency' => $currency,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod ?? 'cash', // USE PROVIDED METHOD OR DEFAULT
                'payment_provider' => 'paystack',
                'description' => 'Home Care Assessment Fee',
            ];
            
            if ($markAsCompleted) {
                $transactionId = $this->generateInternalTransactionId('ASSESS');
                
                $paymentData['status'] = 'completed';
                $paymentData['transaction_id'] = $transactionId;
                $paymentData['paid_at'] = now();
                $paymentData['expires_at'] = null;
                $paymentData['metadata'] = [
                    'payment_method' => $paymentMethod ?? 'cash',
                    'is_admin_created' => true,
                    'created_by_admin' => auth()->id(),
                    'admin_name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                    'created_at' => now()->toIso8601String(),
                    'note' => 'Payment recorded by admin during care request creation'
                ];
            } else {
                $paymentData['status'] = 'pending';
                $paymentData['expires_at'] = now()->addMinutes(30);
            }
            
            return CarePayment::create($paymentData);
            
        } catch (\Exception $e) {
            \Log::error('Failed to create assessment payment', [
                'care_request_id' => $careRequest->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create care payment record
     */
    protected function createCarePayment(
        CareRequest $careRequest, 
        User $patient, 
        ?string $region, 
        bool $markAsCompleted = false,
        ?string $paymentMethod = null // ADD THIS PARAMETER
    ): ?CarePayment
    {
        try {
            $feeStructure = CareFeeStructure::active()
                ->where('care_type', $careRequest->care_type)
                ->whereIn('fee_type', ['daily_rate', 'package'])
                ->first();
            
            if (!$feeStructure) {
                $feeStructure = CareFeeStructure::getAssessmentFee($careRequest->care_type, $region);
            }
            
            if (!$feeStructure) {
                $amount = 500.00;
                $taxAmount = $amount * 0.15;
                $totalAmount = $amount + $taxAmount;
                $currency = 'GHS';
            } else {
                $baseAmount = $feeStructure->getAmountForRegion($region);
                $amount = $baseAmount * 5;
                $taxAmount = $amount * ($feeStructure->tax_percentage / 100);
                $totalAmount = $amount + $taxAmount;
                $currency = $feeStructure->currency;
            }
            
            $paymentData = [
                'care_request_id' => $careRequest->id,
                'patient_id' => $patient->id,
                'payment_type' => 'care_fee',
                'amount' => $amount,
                'currency' => $currency,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod ?? 'cash', // USE PROVIDED METHOD OR DEFAULT
                'payment_provider' => 'paystack',
                'description' => 'Home Care Service Fee',
            ];
            
            if ($markAsCompleted) {
                $transactionId = $this->generateInternalTransactionId('CARE');
                
                $paymentData['status'] = 'completed';
                $paymentData['transaction_id'] = $transactionId;
                $paymentData['paid_at'] = now();
                $paymentData['expires_at'] = null;
                $paymentData['metadata'] = [
                    'payment_method' => $paymentMethod ?? 'cash',
                    'is_admin_created' => true,
                    'created_by_admin' => auth()->id(),
                    'admin_name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                    'created_at' => now()->toIso8601String(),
                    'note' => 'Payment recorded by admin during care request creation'
                ];
            } else {
                $paymentData['status'] = 'pending';
                $paymentData['expires_at'] = now()->addDays(7);
            }
            
            return CarePayment::create($paymentData);
            
        } catch (\Exception $e) {
            \Log::error('Failed to create care payment', [
                'care_request_id' => $careRequest->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate internal transaction ID for admin-created payments
     */
    protected function generateInternalTransactionId(string $prefix = 'TXN'): string
    {
        $date = now()->format('Ymd');
        $time = now()->format('His');
        $random = strtoupper(substr(uniqid(), -6));
        
        return "{$prefix}-{$date}-{$time}-{$random}";
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
                'reason' => 'required|string|min:1',
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