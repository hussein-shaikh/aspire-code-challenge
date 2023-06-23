<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\AuthService;
use App\Models\User;
use App\Models\UserCompanyMapping;
use App\Models\UserRoleMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


    public function Register(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'name' => ["required", "string"],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validatedData->fails()) {
            return response([
                'status' => false,
                'error' => $validatedData->errors()->messages(),
                'data' => [],
                'message' => 'Registration Failed'
            ], 400);
        }

        $getUser = $this->authService->createUser($request->all());

        if ($getUser !== false) {
            $accessToken = $getUser->createToken('tokens')->plainTextToken;
            return response([
                'status' => true,
                'token' => $accessToken,
                'data' => [],
                'message' => "Registration Successful"
            ], 200);
        }

        return response([
            'status' => false,
            'error' => ["all" => "Something went wrong"],
            'data' => [],
            'message' => "Invalid Request"

        ], 400);
    }

    public function login(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
            'password' => ['required', 'string'],
            'remember_me' => ["nullable", "boolean"]
        ]);

        if ($validateData->fails()) {
            return response([
                'status' => false,
                'error' => $validateData->errors(),
                'data' => [],
                'message' => 'Login Failed'
            ], 400);
        }

        $loginUser = $this->authService->loginUser($request->all());

        if ($loginUser !== false) {
            return response()->json([
                'status' => true,
                'data' => ['token' => $loginUser],
                'error' => [],
                "message" => "Login successfulI",
            ]);
        }
        return response([
            'status' => false,
            'error' => ["all" => "Login failedI"],
            'data' => [],
            'message' => 'Login Failed'
        ], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'data' => [],
            'message' => 'Successfully logged out'
        ]);
    }
}
