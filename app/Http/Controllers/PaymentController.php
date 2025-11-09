<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CarePayment;
use App\Models\CareRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Get all payments with filters and pagination
     * GET /api/payments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->query('search');
            $status = $request->query('status');
            $paymentType = $request->query('payment_type');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $query = CarePayment::with([
                'patient:id,first_name,last_name,phone,email',
                'careRequest:id,care_type,status'
            ])->orderBy('created_at', 'desc');

            // Search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                      ->orWhere('transaction_id', 'like', "%{$search}%")
                      ->orWhereHas('patient', function ($patientQuery) use ($search) {
                          $patientQuery->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('last_name', 'like', "%{$search}%")
                                      ->orWhere('phone', 'like', "%{$search}%")
                                      ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Status filter
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            // Payment type filter
            if ($paymentType && $paymentType !== 'all') {
                $query->where('payment_type', $paymentType);
            }

            // Date range filters
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments.',
            ], 500);
        }
    }

    /**
     * Get payment statistics
     * GET /api/payments/statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            // Basic counts by status
            $totalPayments = CarePayment::count();
            $completedPayments = CarePayment::where('status', 'completed')->count();
            $pendingPayments = CarePayment::whereIn('status', ['pending', 'processing'])->count();
            $failedPayments = CarePayment::where('status', 'failed')->count();

            // Revenue calculations
            $totalRevenue = CarePayment::where('status', 'completed')->sum('total_amount');
            $assessmentRevenue = CarePayment::where('status', 'completed')
                ->where('payment_type', 'assessment_fee')
                ->sum('total_amount');
            $careRevenue = CarePayment::where('status', 'completed')
                ->where('payment_type', 'care_fee')
                ->sum('total_amount');

            // This month's revenue
            $thisMonthRevenue = CarePayment::where('status', 'completed')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('total_amount');

            // Last month's revenue for comparison
            $lastMonthRevenue = CarePayment::where('status', 'completed')
                ->whereYear('paid_at', now()->subMonth()->year)
                ->whereMonth('paid_at', now()->subMonth()->month)
                ->sum('total_amount');

            // Calculate month-over-month change
            $revenueChange = 0;
            if ($lastMonthRevenue > 0) {
                $revenueChange = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
            } elseif ($thisMonthRevenue > 0) {
                $revenueChange = 100;
            }

            // Average payment amount
            $averagePaymentAmount = CarePayment::where('status', 'completed')
                ->avg('total_amount');

            // Payment method breakdown
            $paymentMethodBreakdown = CarePayment::where('status', 'completed')
                ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_method => [
                        'count' => $item->count,
                        'total' => $item->total
                    ]];
                });

            // Recent payments trend (last 7 days)
            $recentTrend = CarePayment::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, count(*) as count, sum(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $stats = [
                'total_payments' => $totalPayments,
                'completed_payments' => $completedPayments,
                'pending_payments' => $pendingPayments,
                'failed_payments' => $failedPayments,
                'total_revenue' => round($totalRevenue, 2),
                'assessment_revenue' => round($assessmentRevenue, 2),
                'care_revenue' => round($careRevenue, 2),
                'this_month_revenue' => round($thisMonthRevenue, 2),
                'revenue_change_percentage' => round($revenueChange, 2),
                'average_payment_amount' => round($averagePaymentAmount, 2),
                'payment_method_breakdown' => $paymentMethodBreakdown,
                'recent_trend' => $recentTrend,
                'completion_rate' => $totalPayments > 0 
                    ? round(($completedPayments / $totalPayments) * 100, 2) 
                    : 0
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching payment statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment statistics.',
            ], 500);
        }
    }

    /**
     * Get a specific payment
     * GET /api/payments/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $payment = CarePayment::with([
                'patient:id,first_name,last_name,phone,email',
                'careRequest.assignedNurse:id,first_name,last_name,phone',
                'careRequest.medicalAssessment'
            ])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment.',
            ], 500);
        }
    }

    /**
     * Export payments to CSV
     * GET /api/payments/export
     */
    public function export(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Use the same filtering logic as index method
            $query = CarePayment::with([
                'patient:id,first_name,last_name,phone,email',
                'careRequest:id,care_type,status'
            ])->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                      ->orWhere('transaction_id', 'like', "%{$search}%")
                      ->orWhereHas('patient', function ($patientQuery) use ($search) {
                          $patientQuery->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('last_name', 'like', "%{$search}%")
                                      ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_type') && $request->payment_type !== 'all') {
                $query->where('payment_type', $request->payment_type);
            }

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Get all matching payments (no pagination for export)
            $payments = $query->get();

            // Generate filename with timestamp
            $filename = 'payments_export_' . now()->format('Y-m-d_H-i-s');
            
            // Add filter info to filename if applied
            $filterParts = [];
            if ($request->filled('status') && $request->status !== 'all') {
                $filterParts[] = 'status-' . $request->status;
            }
            if ($request->filled('payment_type') && $request->payment_type !== 'all') {
                $filterParts[] = 'type-' . $request->payment_type;
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
            $callback = function() use ($payments) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for proper UTF-8 encoding in Excel
                fwrite($file, "\xEF\xBB\xBF");
                
                // CSV Headers
                $headers = [
                    'Payment ID',
                    'Reference Number',
                    'Transaction ID',
                    'Patient Name',
                    'Patient Phone',
                    'Patient Email',
                    'Payment Type',
                    'Payment Method',
                    'Amount',
                    'Tax Amount',
                    'Total Amount',
                    'Currency',
                    'Status',
                    'Description',
                    'Care Type',
                    'Care Request Status',
                    'Payment Provider',
                    'Provider Reference',
                    'Failure Reason',
                    'Created At',
                    'Paid At',
                    'Expires At',
                    'Refunded At',
                    'Admin Created',
                    'Admin Creator',
                ];
                
                fputcsv($file, $headers);
                
                // Add data rows
                foreach ($payments as $payment) {
                    $row = [
                        $payment->id,
                        $payment->reference_number,
                        $payment->transaction_id ?: '',
                        $payment->patient ? $payment->patient->first_name . ' ' . $payment->patient->last_name : 'N/A',
                        $payment->patient ? $payment->patient->phone : 'N/A',
                        $payment->patient ? $payment->patient->email : 'N/A',
                        ucwords(str_replace('_', ' ', $payment->payment_type)),
                        ucwords(str_replace('_', ' ', $payment->payment_method ?: 'N/A')),
                        number_format($payment->amount, 2),
                        number_format($payment->tax_amount, 2),
                        number_format($payment->total_amount, 2),
                        $payment->currency,
                        ucfirst($payment->status),
                        $payment->description ?: '',
                        $payment->careRequest ? ucwords(str_replace('_', ' ', $payment->careRequest->care_type)) : 'N/A',
                        $payment->careRequest ? ucwords(str_replace('_', ' ', $payment->careRequest->status)) : 'N/A',
                        $payment->payment_provider ?: '',
                        $payment->provider_reference ?: '',
                        $payment->failure_reason ?: '',
                        $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : '',
                        $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : '',
                        $payment->expires_at ? $payment->expires_at->format('Y-m-d H:i:s') : '',
                        $payment->refunded_at ? $payment->refunded_at->format('Y-m-d H:i:s') : '',
                        $payment->isAdminCreated() ? 'Yes' : 'No',
                        $payment->getAdminCreator() ?: '',
                    ];
                    
                    fputcsv($file, $row);
                }
                
                fclose($file);
            };

            // Log the export action
            \Log::info('Payments exported', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'count' => $payments->count(),
                'filters' => $request->only(['status', 'payment_type', 'search', 'start_date', 'end_date']),
                'filename' => $filename
            ]);

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error exporting payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to export payments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}