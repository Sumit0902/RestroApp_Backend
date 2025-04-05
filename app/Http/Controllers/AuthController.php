<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

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

        // Check if 2FA is enabled for the user
        $twoFactorEnabled = !is_null($user->two_factor_secret);

        if ($twoFactorEnabled) {
            // Store user ID in session or a temporary state for 2FA verification
            // $request->session()->put('login.id', $user->id);
            return response()->json([
                'message' => '2FA required',
                'two_factor' => true
            ], 200);
        }

        
        $token = $user->createToken(($user->firstname.$user->lastname) . '-AuthToken')->plainTextToken;
        return response()->json([
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'company_role' => $user->company_role,
            'company' => $user->company,
            'phone' => $user->phone,
            'wage' => $user->wage,
            'wage_rate' => $user->wage_rate,
            'two_factor_enabled' => $twoFactorEnabled, // Include 2FA status in the response
            'access_token' => $token,
        ]);
    }


    public function verifyTwoFactor(Request $request) {

        $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid session'], 401);
        }

        $code = $request->code;
        // $valid = app('two.factor')->verify(decrypt($user->two_factor_secret), $code);
        $valid = app(TwoFactorAuthenticationProvider::class)->verify(decrypt($user->two_factor_secret), $code);

        if (!$valid) {
            return response()->json([
                'message' => 'Invalid 2FA code'
            ], 401);
        }

        $token = $user->createToken(($user->firstname . $user->lastname) . '-AuthToken')->plainTextToken;
        
        $twoFactorEnabled = !is_null($user->two_factor_secret);
        return response()->json([
           'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'company_role' => $user->company_role,
            'company' => $user->company,
            'phone' => $user->phone,
            'wage' => $user->wage,
            'wage_rate' => $user->wage_rate,
            'two_factor_enabled' => $twoFactorEnabled, // Include 2FA status in the response
            'access_token' => $token,
        ]);
    }

    public function logout(Request $request){
        try {
            //code...
            auth()->user()->tokens()->delete();
        
            return response()->json([
              "message"=>"logged out"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "message"=>"error logged out"
            ], 400);
        }
    }
}
