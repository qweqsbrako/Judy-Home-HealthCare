<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CareRequest;
use App\Models\CarePayment;
use App\Models\CareFeeStructure;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CareRequestController extends Controller
{
    protected $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Get Paystack public key and supported channels
     * GET /api/mobile/care-requests/payment-config
     */
    public function getPaymentConfig(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'public_key' => $this->paystackService->getPublicKey(),
                    'supported_channels' => $this->paystackService->getSupportedChannels(),
                    'currency' => 'GHS',
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching payment config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment configuration.',
            ], 500);
        }
    }

    /**
     * Get assessment fee and care request process info
     * GET /api/mobile/care-requests/info
     */
    public function getRequestInfo(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();
            
            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can request care.'
                ], 403);
            }

            $careType = $request->query('care_type');
            $region = $request->query('region');

            $assessmentFee = CareFeeStructure::getAssessmentFee($careType, $region);

            if (!$assessmentFee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assessment fee not configured.'
                ], 404);
            }

            $assessmentAmount = $assessmentFee->getAmountForRegion($region);
            $assessmentTotal = $assessmentFee->getTotalForRegion($region);

            return response()->json([
                'success' => true,
                'data' => [
                    'assessment_fee' => [
                        'amount' => $assessmentAmount,
                        'tax' => $assessmentAmount * ($assessmentFee->tax_percentage / 100),
                        'total' => $assessmentTotal,
                        'currency' => $assessmentFee->currency,
                        'description' => $assessmentFee->description,
                    ],
                    'process_steps' => [
                        [
                            'step' => 1,
                            'title' => 'Request Care',
                            'description' => 'Submit your care request with details about your needs',
                            'icon' => 'request',
                        ],
                        [
                            'step' => 2,
                            'title' => 'Pay Assessment Fee',
                            'description' => 'Pay the assessment fee to begin the process',
                            'icon' => 'payment',
                        ],
                        [
                            'step' => 3,
                            'title' => 'Nurse Assignment',
                            'description' => 'A qualified nurse will be assigned to you',
                            'icon' => 'nurse',
                        ],
                        [
                            'step' => 4,
                            'title' => 'Home Assessment',
                            'description' => 'The nurse conducts a comprehensive assessment at your home',
                            'icon' => 'assessment',
                        ],
                        [
                            'step' => 5,
                            'title' => 'Review & Care Plan',
                            'description' => 'Our team reviews the assessment and creates your personalized care plan',
                            'icon' => 'review',
                        ],
                        [
                            'step' => 6,
                            'title' => 'Care Payment',
                            'description' => 'Pay for the care services based on your care plan',
                            'icon' => 'care_payment',
                        ],
                        [
                            'step' => 7,
                            'title' => 'Care Begins',
                            'description' => 'Your home care services begin as scheduled',
                            'icon' => 'care_start',
                        ],
                    ],
                    'care_types' => [
                        ['value' => 'general_nursing', 'label' => 'General Nursing'],
                        ['value' => 'elderly_care', 'label' => 'Elderly Care'],
                        ['value' => 'post_surgical', 'label' => 'Post-Surgical Care'],
                        ['value' => 'chronic_disease', 'label' => 'Chronic Disease Management'],
                        ['value' => 'palliative_care', 'label' => 'Palliative Care'],
                        ['value' => 'rehabilitation', 'label' => 'Rehabilitation'],
                        ['value' => 'wound_care', 'label' => 'Wound Care'],
                        ['value' => 'medication_management', 'label' => 'Medication Management'],
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care request info: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch request information.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all care requests for the patient
     * GET /api/mobile/care-requests
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can view care requests.'
                ], 403);
            }

            $perPage = $request->input('per_page', 15);
            $status = $request->query('status');

            $query = CareRequest::forPatient($patient->id)
                ->with(['assignedNurse:id,first_name,last_name,phone', 'assessmentPayment', 'carePayment'])
                ->recent();

            if ($status) {
                $query->byStatus($status);
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
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get a specific care request
     * GET /api/mobile/care-requests/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can view care requests.'
                ], 403);
            }

            $careRequest = CareRequest::with([
                'assignedNurse:id,first_name,last_name,phone,email',
                'medicalAssessment',
                'assessmentPayment',
                'carePayment'
            ])
                ->forPatient($patient->id)
                ->find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $careRequest
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch care request.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a new care request
     * POST /api/mobile/care-requests
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can request care.'
                ], 403);
            }

            $validator = Validator::make(
                $request->all(),
                CareRequest::validationRules(),
                CareRequest::validationMessages()
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Create care request
                $careRequest = CareRequest::create([
                    'patient_id' => $patient->id,
                    'care_type' => $request->care_type,
                    'urgency_level' => $request->urgency_level ?? 'routine',
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
                    'status' => 'pending_payment',
                ]);

                // Get assessment fee
                $assessmentFee = CareFeeStructure::getAssessmentFee($request->care_type, $request->region);

                if (!$assessmentFee) {
                    throw new \Exception('Assessment fee not configured.');
                }

                $amount = $assessmentFee->getAmountForRegion($request->region);
                $taxAmount = $amount * ($assessmentFee->tax_percentage / 100);
                $totalAmount = $amount + $taxAmount;

                // Create assessment payment record
                $payment = CarePayment::create([
                    'care_request_id' => $careRequest->id,
                    'patient_id' => $patient->id,
                    'payment_type' => 'assessment_fee',
                    'amount' => $amount,
                    'currency' => $assessmentFee->currency,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'description' => 'Home Care Assessment Fee',
                    'expires_at' => now()->addMinutes(30),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Care request created successfully.',
                    'data' => [
                        'care_request' => $careRequest->load('assessmentPayment'),
                        'payment' => $payment,
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error creating care request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create care request.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cancel a care request
     * POST /api/mobile/care-requests/{id}/cancel
     */
    public function cancel($id, Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can cancel care requests.'
                ], 403);
            }

            $careRequest = CareRequest::forPatient($patient->id)->find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            if (!$careRequest->can_cancel) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be cancelled at this stage.'
                ], 400);
            }

            $careRequest->cancel($request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Care request cancelled successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error cancelling care request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel care request.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Initialize Paystack payment
     * POST /api/mobile/care-requests/{id}/payment/initialize
     */
    public function initiatePayment($id, Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can make payments.'
                ], 403);
            }

            $careRequest = CareRequest::forPatient($patient->id)->find($id);

            if (!$careRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care request not found.'
                ], 404);
            }

            // Determine which payment to process
            $paymentType = $careRequest->requires_assessment_payment ? 'assessment_fee' : 'care_fee';
            
            $payment = CarePayment::where('care_request_id', $careRequest->id)
                ->where('payment_type', $paymentType)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending payment found.'
                ], 404);
            }

            // Initialize Paystack transaction
            $paystackResponse = $this->paystackService->initializeTransaction([
                'email' => $patient->email,
                'amount' => $payment->total_amount * 100, // Convert to pesewas
                'reference' => $payment->reference_number,
                'currency' => $payment->currency,
                'metadata' => [
                    'care_request_id' => $careRequest->id,
                    'payment_type' => $paymentType,
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->first_name . ' ' . $patient->last_name,
                ],
            ]);

            if (!$paystackResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $paystackResponse['message'],
                ], 400);
            }

            // Update payment with Paystack details
            $payment->update([
                'provider_reference' => $paystackResponse['data']['access_code'],
                'status' => 'processing',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully.',
                'data' => [
                    'payment' => $payment->fresh(),
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'access_code' => $paystackResponse['data']['access_code'],
                    'reference' => $paystackResponse['data']['reference'],
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error initiating payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify Paystack payment
     * POST /api/mobile/care-requests/payment/verify
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reference' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payment = CarePayment::where('reference_number', $request->reference)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.'
                ], 404);
            }

            // Verify transaction with Paystack
            $verificationResponse = $this->paystackService->verifyTransaction($request->reference);

            if (!$verificationResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verificationResponse['message'],
                ], 400);
            }

            $transactionData = $verificationResponse['data'];

            // Check if payment was successful
            if ($transactionData['status'] !== 'success') {
                $payment->markAsFailed('Payment was not successful: ' . $transactionData['status']);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed.',
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Mark payment as completed
                $payment->markAsCompleted($transactionData['transaction_id'], [
                    'verified_at' => now(),
                    'channel' => $transactionData['channel'],
                    'customer_code' => $transactionData['customer']['customer_code'],
                    'paid_at' => $transactionData['paid_at'],
                ]);

                // Update care request status
                $careRequest = $payment->careRequest;

                if ($payment->payment_type === 'assessment_fee') {
                    $careRequest->update(['status' => 'payment_received']);
                } elseif ($payment->payment_type === 'care_fee') {
                    $careRequest->update(['status' => 'care_payment_received']);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully.',
                    'data' => [
                        'payment' => $payment->fresh(),
                        'care_request' => $careRequest->fresh(),
                        'transaction' => [
                            'reference' => $transactionData['reference'],
                            'amount' => $transactionData['amount'],
                            'channel' => $transactionData['channel'],
                            'paid_at' => $transactionData['paid_at'],
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error verifying payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Webhook endpoint for Paystack callbacks
     * POST /api/webhooks/paystack
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            $signature = $request->header('x-paystack-signature');
            $body = $request->getContent();
            
            if (!$signature || hash_hmac('sha512', $body, config('paystack.secretKey')) !== $signature) {
                \Log::warning('Invalid Paystack webhook signature');
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $event = $request->input('event');
            $data = $request->input('data');

            \Log::info('Paystack webhook received', ['event' => $event, 'reference' => $data['reference'] ?? null]);

            if ($event === 'charge.success') {
                $payment = CarePayment::where('reference_number', $data['reference'])->first();

                if ($payment && $payment->status !== 'completed') {
                    DB::beginTransaction();

                    try {
                        $payment->markAsCompleted($data['id'], [
                            'webhook_received_at' => now(),
                            'channel' => $data['channel'],
                        ]);

                        $careRequest = $payment->careRequest;

                        if ($payment->payment_type === 'assessment_fee') {
                            $careRequest->update(['status' => 'payment_received']);
                        } elseif ($payment->payment_type === 'care_fee') {
                            $careRequest->update(['status' => 'care_payment_received']);
                        }

                        DB::commit();

                        \Log::info('Webhook processed successfully', ['reference' => $data['reference']]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                }
            }

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            \Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }
}