<?php

namespace App\Http\Services;


use App\Models\LoanRepaymentModel;
use App\Models\LoanRequestModel;
use Illuminate\Support\Str;

class  LoanCalculationService
{


    public function calculatePendingAmount($getLoanDetails)
    {

        $TotalAmount = $TotalPaid = $Pending = $termCompleted = $perTermPayment = 0;
        if (isset($getLoanDetails) && !empty($getLoanDetails)) {
            if (isset($getLoanDetails->repayments)) {
                $getAllRepayments = $getLoanDetails->repayments;

                foreach ($getAllRepayments as $repayment) {
                    if ($repayment->payment_status == config("constants.PAYMENT_STATUS.COMPLETED")) {

                        $TotalPaid += $repayment->paid_amount;
                        $termCompleted++;
                    }
                }
            }

            $termRemaining = $getLoanDetails->term - $termCompleted;
            $InterestToBePaid = ($getLoanDetails->amount * $getLoanDetails->interest_percentage) / $getLoanDetails->term;
            $TotalAmount = $InterestToBePaid + $getLoanDetails->amount;
            $Pending = $TotalAmount - $TotalPaid;
            if ($termRemaining > 0) {
                $perTermPayment = $Pending / $termRemaining;
            }
        }
        return ["total_amount" => $TotalAmount, "paid" => $TotalPaid, "pending" => $Pending, "per_term" => $perTermPayment, "terms_completed" => $termCompleted];
    }


    public function viewLoans($user_id, $search = "")
    {
        $getUserLoans = LoanRequestModel::where("user_id", $user_id)->whereNull("deleted_at");

        if (!empty($search)) {
            $getUserLoans = $getUserLoans->where("id", $search);
        }

        $getUserLoans = $getUserLoans->paginate(config("constants.GLOBAL_PAGINATION_COUNT"));

        return $getUserLoans;
    }


    public function createLoan($user_id, $params = [])
    {
        $checkIfExists = LoanRequestModel::where("user_id", $user_id)->where("status", "<>", config("constants.LOAN_STATES.PAID"))->get();
        if ($checkIfExists->count() < 2) {

            $reqArray["amount"] = $params["amount"];
            $reqArray["interest_percentage"] = $params["interest_percentage"];
            $reqArray["term"] = $params["term"];
            $reqArray["govt_id"] = $params["govt_id"];
            $reqArray["id"] = Str::uuid();
            $reqArray["user_id"] = $user_id;
            $reqArray["status"] = config("constants.LOAN_STATES.PENDING");
            $reqArray["is_active"] = 1;

            return LoanRequestModel::create($reqArray);
        }
        return false;
    }


    public function repayLoan($loan_id, $user_id, $params = [])
    {
        $getUserLoanDetails = LoanRequestModel::where("user_id", $user_id)->whereNull("deleted_at")->where("id", $loan_id)->where("status", config("constants.LOAN_STATES.APPROVED"))->first();

        if (!empty($getUserLoanDetails) && $getUserLoanDetails->status != config("constants.LOAN_STATES.PAID")) {

            $checkAmountDetails = $this->calculatePendingAmount($getUserLoanDetails);

            if (strtoupper($params["payment_status"]) !== "COMPLETED") {

                $insertTerm = LoanRepaymentModel::create([
                    "loan_id" => $loan_id,
                    "user_id" => $user_id,
                    "paid_amount" => $params["paid_amount"],
                    "term_count" => ($checkAmountDetails["terms_completed"] + 1),
                    "is_active" => 1,
                    "payment_status" => config("constants.PAYMENT_STATUS." . strtoupper($params["payment_status"]))
                ]);

                return ["type" => "payment_failed", "message" => "Payment Failed"];
            }

            if ($params["paid_amount"] >= round($checkAmountDetails["per_term"])) {

                $insertTerm = LoanRepaymentModel::create([
                    "loan_id" => $loan_id,
                    "user_id" => $user_id,
                    "paid_amount" => $params["paid_amount"],
                    "term_count" => ($checkAmountDetails["terms_completed"] + 1),
                    "is_active" => 1,
                    "payment_status" => config("constants.PAYMENT_STATUS.COMPLETED")
                ]);
                if ($insertTerm) {
                    $getLoanDetails = LoanRequestModel::where("id", $loan_id)->first();
                    $data = [];
                    $message = 'Amount Paid successfully';
                    $recalc = $this->calculatePendingAmount($getLoanDetails);
                    $data["pending_amount"] = $recalc["pending"];

                    if ($recalc["pending"] < 1) {
                        $c = LoanRequestModel::where("id", $loan_id)->update(["status" => config("constants.LOAN_STATES.PAID")]);
                        if ($c) {
                            $message = "Amount paid & loan completed successfully";
                        }
                    }
                    return ["type" => "payment_success", "message" => $message];
                }
            }
        }
        return ["type" => "failure", "message" => "Something Went Wrong"];
    }
    public function viewloanWithStates($state, $searchParams = [])
    {

        $getAllLoans = LoanRequestModel::where("status", config("constants.LOAN_STATES")[strtoupper($state)])->where("is_active", 1);

        if (isset($searchParams["searchByUser"]) && !empty($searchParams["searchByUser"])) {
            $getAllLoans = $getAllLoans->where("user_id", $searchParams["searchByUser"]);
        }

        if (isset($searchParams["searchByLoan"]) && !empty($searchParams["searchByLoan"])) {
            $getAllLoans = $getAllLoans->where("id", $searchParams["searchByLoan"]);
        }

        $getAllLoans = $getAllLoans->paginate(config("constants.GLOBAL_PAGINATION_COUNT"));

        return $getAllLoans;
    }


    public function loanApproval($loan_id)
    {
        $getLoanDetails = LoanRequestModel::where("id", $loan_id)->first();
        if (empty($getLoanDetails) || (isset($getLoanDetails->status) && $getLoanDetails->status != "PENDING")) {
            return false;
        }

        $approve = LoanRequestModel::where("id", $loan_id)->update(["status" => config("constants.LOAN_STATES.APPROVED")]);
        if ($approve) {
            return true;
        }
        return false;
    }

    public function loanRejection($loan_id)
    {
        $getLoanDetails = LoanRequestModel::where("id", $loan_id)->first();
        if (empty($getLoanDetails) || (isset($getLoanDetails->status) && ($getLoanDetails->status != "PENDING" || $getLoanDetails->status == "REJECTED"))) {
            return false;
        }

        $reject = LoanRequestModel::where("id", $loan_id)->update(["status" => config("constants.LOAN_STATES.REJECTED")]);
        if ($reject) {
            return true;
        }

        return false;
    }
}
