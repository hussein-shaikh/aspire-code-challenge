<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\LoanCalculationService;
use App\Models\LoanRequestModel;
use Illuminate\Http\Request;

class LoanController extends Controller
{

    private $loanService;
    public function __construct(LoanCalculationService $loanService)
    {
        $this->loanService = $loanService;
    }

    public function viewLoan(Request $request, $type = "PENDING")
    {

        if (!in_array(strtoupper($type), array_keys(config("constants.LOAN_STATES")))) {
            return response()->json([
                'status' => false,
                'data' => [],
                'error' => ["all" => "Loan type doesnt exists"],
                'message' => "Loan type doesnt exists"
            ]);
        }

        $getAllLoans = $this->loanService->viewloanWithStates($type, $request->all());

        if ($getAllLoans->count() > 0) {
            return response()->json([
                'status' => true,
                'data' => $getAllLoans,
                'error' => [],
                'message' => "Request successful"
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [],
            'error' => ["all" => "No loan requests found"],
            'message' => "No loan requests found"
        ]);
    }


    public function approveLoanRequest($id, Request $request)
    {

        $approve = $this->loanService->loanApproval($id);
        if ($approve) {
            return response()->json([
                'status' => true,
                'data' => [],
                'error' => [],
                'message' => "Loan Approved successfully"
            ]);
        }

        return response()->json([
            'status' => false,
            'data' => [],
            'error' => ["all" => "Failed to approve loan"],
            'message' => "Failed to approve loan"
        ], 400);
    }

    public function rejectLoanRequest($id, Request $request)
    {

        $rejectLoan = $this->loanService->loanRejection($id);
        if ($rejectLoan) {
            return response()->json([
                'status' => true,
                'data' => [],
                'error' => [],
                'message' => "Loan Rejected successfully"
            ]);
        }
        return response()->json([
            'status' => false,
            'data' => [],
            'error' => ["all" => "Failed to reject loan"],
            'message' => "Failed to reject loan"
        ], 400);
    }
}
