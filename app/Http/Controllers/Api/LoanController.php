<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\LoanCalculationService;
use App\Models\LoanRepaymentModel;
use App\Models\LoanRequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoanController extends Controller
{

    use LoanCalculationService;


    public function createLoan(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', "between:0,50000"],
            'interest_amount' => ['required', "numeric", "between:0,15"],
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

        $checkIfExists = LoanRequestModel::where("user_id", $request->user()->id)->where("status", "<>", config("constants.LOAN_STATES.PAID"))->get();
        if ($checkIfExists->count() < 2) {

            $reqArray = $request->all();
            $reqArray["id"] = Str::uuid();
            $reqArray["user_id"] = $request->user()->id;
            $reqArray["status"] = config("constants.LOAN_STATES.PENDING");
            $reqArray["is_active"] = 1;

            $createLoan = LoanRequestModel::create($reqArray);

            if (isset($createLoan->id)) {
                return response()->json([
                    'status' => true,
                    'data' => [],
                    'error' => [],
                    'message' => "Loan created successfully"
                ]);
            }
        } else {
            return response([
                'status' => false,
                'data' => [],
                'error' => ["all" => "Only 2 active loans allowed"],
                'message' => 'Only 2 active loans allowed'
            ], 400);
        }

        return response()->json([
            'status' => false,
            'data' => [],
            'error' => ["all" => "Failed to create loan request"],
            'message' => "Failed to create loan request"
        ]);
    }

    public function viewLoan(Request $request)
    {
        $getUserLoans = LoanRequestModel::where("user_id", $request->user()->id)->whereNull("deleted_at");

        if ($request->has("search") && !empty($request->search)) {
            $getUserLoans = $getUserLoans->where("id", $request->search);
        }

        $getUserLoans = $getUserLoans->paginate(config("constants.GLOBAL_PAGINATION_COUNT"));

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


        $getUserLoanDetails = LoanRequestModel::where("user_id", $request->user()->id)->whereNull("deleted_at")->where("id", $loanId)->where("status", config("constants.LOAN_STATES.APPROVED"))->first();

        if (!empty($getUserLoanDetails) && $getUserLoanDetails->status != config("constants.LOAN_STATES.PAID")) {


            $checkAmountDetails = $this->calculatePendingAmount($getUserLoanDetails);

            if (strtoupper($request->payment_status) !== "COMPLETED") {

                $insertTerm = LoanRepaymentModel::create([
                    "loan_id" => $loanId,
                    "user_id" => $request->user()->id,
                    "paid_amount" => $request->paid_amount,
                    "term_count" => ($checkAmountDetails["terms_completed"] + 1),
                    "is_active" => 1,
                    "payment_status" => config("constants.PAYMENT_STATUS." . strtoupper($request->payment_status))
                ]);

                return response([
                    'status' => false,
                    'error' => ["all" => "Payment Failed"],
                    'message' => 'Payment Failed'
                ], 400);
            }

            if ($request->paid_amount >= round($checkAmountDetails["per_term"])) {

                $insertTerm = LoanRepaymentModel::create([
                    "loan_id" => $loanId,
                    "user_id" => $request->user()->id,
                    "paid_amount" => $request->paid_amount,
                    "term_count" => ($checkAmountDetails["terms_completed"] + 1),
                    "is_active" => 1,
                    "payment_status" => config("constants.PAYMENT_STATUS.COMPLETED")
                ]);
                if ($insertTerm) {
                    $getLoanDetails = LoanRequestModel::where("id", $loanId)->first();
                    $data = [];
                    $message = 'Amount Paid successfully';
                    $recalc = $this->calculatePendingAmount($getLoanDetails);
                    $data["pending_amount"] = $recalc["pending"];

                    if ($recalc["pending"] < 1) {
                        $c = LoanRequestModel::where("id", $loanId)->update(["status" => config("constants.LOAN_STATES.PAID")]);
                        if ($c) {
                            $message = "Amount paid & loan completed successfully";
                        }
                    }
                    return response([
                        'status' => true,
                        'error' => [],
                        'data' => $data,
                        'message' => $message
                    ], 200);
                }
            } else {
                return response([
                    'status' => false,
                    'error' => ["all" => "Amount is lesser than the term amount , pay an amount of " . ceil($checkAmountDetails["per_term"])],
                    'message' => 'Repayment Failed'
                ], 400);
            }
        }
        return response([
            'status' => false,
            'error' => ["all" => "Payment Failed"],
            'message' => 'Payment Failed'
        ], 400);
    }
}
