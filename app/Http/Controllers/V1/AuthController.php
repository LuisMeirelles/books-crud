<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthTokenRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function token(AuthTokenRequest $request)
    {
        $fields = $request->validated();

        if (!Auth::attempt($fields)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
}
