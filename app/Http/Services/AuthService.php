<?php

namespace App\Http\Services;


use App\Models\LoanRepaymentModel;
use App\Models\LoanRequestModel;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class  AuthService
{

    public function createUser($params = [])
    {

        $params["is_active"] = 1;
        $params["password"] = Hash::make($params["password"]);
        $user = User::create($params);

        if ($user) {
            return $user;
        }

        return false;
    }


    public function loginUser($params = [])
    {
        if (!Auth::attempt(["email" => $params["email"], "password" => $params["password"], "is_active" => 1], $params["remember_me"] ?? false)) {
            return response()->json([
                'status' => false,
                'error' => ["all" => "Invalid Username or Password"],
                'data' => [],
                'message' => 'Invalid Username or Password'
            ], 400);
        }
        try {
            $user = request()->user();
            $tokenResult = $user->createToken('tokens')->plainTextToken;
            return $tokenResult;
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}
