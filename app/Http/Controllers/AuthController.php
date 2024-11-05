<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request) {
        $incomingFields = $request->validate([
            'email'=>'required',
            'password'=>'required'
        ]);

        if(auth()->attempt($incomingFields)) {
            $user = Auth::user();
            return response()->json(['token' => $user->createToken('API token')->plainTextToken]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create($validated);

        return response()->json(['message' => $user->createToken('API token')->plainTextToken]);
    }
}
