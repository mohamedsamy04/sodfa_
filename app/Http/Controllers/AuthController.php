<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $fields = $request->validated();
        $fields['password'] = Hash::make($fields['password']);
        $user = User::create($fields);
        $token = $user->createToken('auth_token');
        return response()->json(
            [
                'user' => $user,
                'token' => $token->plainTextToken
            ]
        );
    }

    public function login(request $request)
    {
        $fields = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required'
        ]);
        $user = User::where('email', $fields['email'])->first();
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials']);
        }
        $token = $user->createToken('auth_token');
        return response()->json(
            [
                'user' => $user,
                'token' => $token->plainTextToken
            ]
        );
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        $fields = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => ['sometimes', 'string', 'regex:/^(010|011|012|015)[0-9]{8}$/'],
            'address' => 'sometimes|string|max:255',
        ]);
        $user = $request->user();
        $user->update($fields);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}




