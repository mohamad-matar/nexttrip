<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|max:50',
            'email'    => 'required|email|max:100|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return api_error($validator->errors() , "Invalid inputs",  Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'email'    => $request->email,
            'password' => $request->password, // سيتم تشفيرها تلقائيًا
            'name'     => $request->name,
        ]);

        // إنشاء preference فارغ تلقائيًا
        $user->preferences()->create([
            'interests' => json_encode([]),
            'trip_pace' => 'متوسط',
            'preferred_activity_level' => 'متوسط',
            'budget_min' => null,
            'budget_max' => null,
        ]);

        $token = $user->createToken("mobile")->plainTextToken;

        return api_success(
            ['token' => $token],
            "Account created successfully",
            Response::HTTP_CREATED
        );
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return api_error($validator->errors() , "Invalid inputs",  Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return api_error("Invalid credentials", null, Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken("mobile")->plainTextToken;

        return api_success(
            ['token' => $token],
            "Account login successfully",
            Response::HTTP_OK
        );
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user?->currentAccessToken()->delete();

        return api_success(null, "Logout successful");
    }
}
