<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\LoanCalculationService;
use App\Models\LoanRepaymentModel;
use App\Models\LoanRequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{

    private $loanService;
    public function __construct(LoanCalculationService $loanService)
    {
        $this->loanService = $loanService;
    }

    public function createLoan(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', "between:0,50000"],
            'interest_percentage' => ['required', "numeric", "between:0,15"],
            'term' => ['required', 'numeric', "between:1,12"],
            'govt_id' => ['required', 'string']
        ]);

        if ($validatedData->fails()) {
            return response([
                'status' => false,
                'error' => $validatedData->errors(),
                'message' => 'Loan Request Failed'
            ], 400);
        }

        $createLoanService = $this->loanService->createLoan($request->user()->id, $request->all());

        if ($createLoanService !== false) {
            return response()->json([
                'status' => true,
                'data' => [],
                'error' => [],
                'message' => "Loan created successfully"
            ]);
        } else {
            return response([
                'status' => false,
                'data' => [],
                'error' => ["all" => "Failed to create loan / cannot create more than 2 loans"],
                'message' => 'Failed to create loan'
            ], 400);
        }
    }

    public function viewLoan(Request $request)
    {
        $getUserLoans = $this->loanService->viewLoans($request->user()->id, $request->get('search'));

        if ($getUserLoans->count() < 1) {
            return response()->json([
                'status' => true,
                'data' => [],
                'error' => [],
                'message' => "No loan requests found"
            ]);
        }
        return response()->json([
            'status' => true,
            'data' => $getUserLoans,
            'error' => [],
            'message' => "Request successfulI"
        ]);
    }

    public function repayLoan(Request $request, $loanId)
    {

        $validatedData = Validator::make($request->all(), [
            'paid_amount' => ['required', 'numeric', "between:0,50000"],
            'payment_status' => ['required', "string"]
        ]);

        if ($validatedData->fails()) {
            return response([
                'status' => false,
                'error' => $validatedData->errors(),
                'message' => 'Loan Request Failed'
            ], 400);
        }

        $repayLoan = $this->loanService->repayLoan($loanId, $request->user()->id, $request->all());

        if (isset($repayLoan["type"])) {
            if ($repayLoan["type"] == "payment_success") {
                return response([
                    'status' => true,
                    'error' => [],
                    "data" => [],
                    'message' => $repayLoan["message"] ?? "Payment Successfull"
                ], 200);
            }
        }
        return response([
            'status' => false,
            'error' => [],
            "data" => [],
            'message' => $repayLoan["message"] ?? 'Repayment Failed'
        ], 400);
    }
}
