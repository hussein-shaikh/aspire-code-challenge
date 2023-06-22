<?php

namespace App\Http\Services;


use App\Models\LoanRepaymentModel;
use App\Models\LoanRequestModel;
use Illuminate\Http\Request;

trait  LoanCalculationService
{


    function calculatePendingAmount($getLoanDetails)
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
            if($termRemaining > 0){
                $perTermPayment = $Pending / $termRemaining;
            }
        }
        return ["total_amount" => $TotalAmount, "paid" => $TotalPaid, "pending" => $Pending, "per_term" => $perTermPayment, "terms_completed" => $termCompleted];
    }
}
