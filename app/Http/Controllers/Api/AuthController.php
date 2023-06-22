<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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


    public function Register(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'name' => ["required","string"],
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


        $request->merge(["is_active" => 1,"password" => Hash::make($request->password)]);
        $user = User::create($request->all());


        if (isset($user->id)) {
            $accessToken = $user->createToken('tokens')->plainTextToken;
            return response([
                'status' => true,
                'token' => $accessToken,
                'data' => [],
                'message' => "Registration Successful"
            ], 200);
        }

        return response([
            'status' => false,
            'error' => ["all"=>"Something went wrong"],
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

        if (!Auth::attempt(["email" => $request->email, "password" => $request->password, "is_active" => 1], $request->remember_me)) {
            return response()->json([
                'status' => false,
                'error' => ["all"=>"Invalid Username or Password"],
                'data' => [],
                'message' => 'Invalid Username or Password'
            ], 400);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('tokens')->plainTextToken;

        return response()->json([
            'status' => true,
            'data' => ['token' => $tokenResult],
            'error' => [],
            "message" => "Login successfulI"
            ,
        ]);
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
