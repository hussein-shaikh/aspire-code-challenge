<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanRequestModel;
use Illuminate\Http\Request;

class LoanController extends Controller
{


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

        $getAllLoans = LoanRequestModel::where("status", config("constants.LOAN_STATES")[strtoupper($type)])->where("is_active", 1);

        if ($request->has("searchByUser") && !empty($request->searchByUser)) {
            $getAllLoans = $getAllLoans->where("user_id", $request->searchByUser);
        }

        if ($request->has("searchByLoan") && !empty($request->searchByLoan)) {
            $getAllLoans = $getAllLoans->where("id", $request->searchByLoan);
        }

        $getAllLoans = $getAllLoans->paginate(config("constants.GLOBAL_PAGINATION_COUNT"));


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

        $getLoanDetails = LoanRequestModel::where("id", $id)->first();
        if (empty($getLoanDetails) || (isset($getLoanDetails->status) && $getLoanDetails->status != "PENDING")) {
            return response()->json([
                'status' => false,
                'data' => [],
                'error' => ["all" => "No loan requests found / not in pending state"],
                'message' => "No loan requests found / not in pending state"
            ], 400);
        }

        $approve = LoanRequestModel::where("id", $id)->update(["status" => config("constants.LOAN_STATES.APPROVED")]);
        if ($approve) {
            return response()->json([
                'status' => true,
                'data' => [],
                'error' => [],
                'message' => "Loan Approved successfully"
            ]);
        }
    }

    public function rejectLoanRequest($id, Request $request)
    {

        $getLoanDetails = LoanRequestModel::where("id", $id)->first();
        if (empty($getLoanDetails) || (isset($getLoanDetails->status) && ($getLoanDetails->status != "PENDING" || $getLoanDetails->status == "REJECTED"))) {
            return response()->json([
                'status' => false,
                'data' => [],
                'error' => ["all" => "No loan requests found / not in pending state / already rejected"],
                'message' => "No loan requests found / not in pending state / already rejected"
            ], 400);
        }

        $approve = LoanRequestModel::where("id", $id)->update(["status" => config("constants.LOAN_STATES.REJECTED")]);
        if ($approve) {
            return response()->json([
                'status' => true,
                'data' => [],
                'error' => [],
                'message' => "Loan Rejected successfully"
            ]);
        }
    }
}
