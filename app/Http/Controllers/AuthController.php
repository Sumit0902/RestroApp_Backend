<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $loginUserData = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|min:8'
        ]);
        $user = User::where('email', $loginUserData['email'])->first();
        if (!$user || !Hash::check($loginUserData['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid Credentials'
            ], 401);
        }
        $token = $user->createToken(($user->firstname.$user->lastname) . '-AuthToken')->plainTextToken;
        return response()->json([
            'id'=> $user->id,
            'firstname'=> $user->firstname,
            'lastname'=> $user->lastname,
            'email'=> $user->email,
            'avatar'=> $user->avatar,
            'role'=> $user->role,
            'company_role' => $user->company_role,
            'company' => $user->company,
            'access_token' => $token,
        ]);
    }

    public function logout(){
        auth()->user()->tokens()->delete();
    
        return response()->json([
          "message"=>"logged out"
        ]);
    }
}
