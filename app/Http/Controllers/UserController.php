<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
class UserController extends Controller
{
    //

    public function store(Request $request) {

     
        $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:15',
            'role' => ['required', Rule::in(['employee', 'manager'])],
            'company_id' => 'required|exists:companies,id',
            'company_role' => 'nullable|string|max:255',
            'wage' => 'nullable|numeric',
            'wage_rate' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate avatar image
        ]);
        try {
            $employeeData = $request->except('avatar');
            $employeeData['avatar'] = null;
            // Handle avatar upload if provided
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $allowedExtensions = ['webp', 'svg', 'png', 'jpeg', 'jpg', 'gif'];
                $extension = $file->getClientOriginalExtension();
                $maxSize = 10 * 1024 * 1024; // 10 MB
    
                if (!in_array($extension, $allowedExtensions)) {
                    return response()->json([
                        'success' => false,
                        'data' => null,
                        'error' => 'Invalid file type. Only webp, svg, png, jpeg, jpg, and gif are allowed.',
                    ], 400);
                }
    
                if ($file->getSize() > $maxSize) {
                    return response()->json([
                        'success' => false,
                        'data' => null,
                        'error' => 'File size exceeds the 10MB limit.',
                    ], 400);
                }
    
                // Generate a random 12-character string
                $randomString = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
                // Get the original filename without the extension
                $originalFilename = pathinfo($request->file('avatar')->getClientOriginalName(), PATHINFO_FILENAME);

                // Replace spaces and invalid characters with "_"
                $sanitizedFilename = preg_replace('/[^A-Za-z0-9\-.]+/', '_', $originalFilename);

                // Create the new filename
                $filename = $sanitizedFilename . '_' . $randomString . '.' . $extension;
                // Store the file in the public disk
                // Store the file in the public disk
                $avatarPath = $file->storeAs('', $filename, 'public');
                $employeeData['avatar'] = $avatarPath; // Add the logo path to the company data
            }
    
            // Hash the password before storing it
            $employeeData['password'] = bcrypt($employeeData['password']);
    
            $user = User::create($employeeData);
    
            return response()->json([
                'success' => true,
                'data' => $user,
                'error' => null, 
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
            ], 400);
        } 
    }

    public function enableTwoFactor(Request $request,$companyId, $employeeId){
        // $user = $request->user();
        try {
            $user = User::find($employeeId);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
            ],400);
        }

        
        app(EnableTwoFactorAuthentication::class)($user);

        $qrCode = $user->twoFactorQrCodeSvg();

        return response()->json([
            'qr_code' => $qrCode,
            'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
            'two_factor_secret' => $user->two_factor_secret, // Optional, for manual entry
        ]);
    }

    public function confirmTwoFactor(Request $request,$companyId, $employeeId ) {
        try {
            $user = User::find($employeeId);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
            ],400);
        }
        
        $request->validate([
            'code' => 'required|string',
        ]);

        // $user = $request->user(); // Authenticated via Sanctum

        if (!$user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => '2FA not initialized', 
            ],400);
        }

        // Verify the 2FA code
        // $valid = app('two.factor')->verify(decrypt($user->two_factor_secret), $request->code);
        $valid = app(TwoFactorAuthenticationProvider::class)->verify(decrypt($user->two_factor_secret), $request->code);
        if (!$valid) { 
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => 'Invalid 2FA code', 
            ],401);
        }

        // Mark 2FA as confirmed (Fortify handles this internally, but we ensure it persists)
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return response()->json([
            'success' => false,
            'data' => '2FA successfully enabled',
            'error' => null, 
        ],200);
    }

    public function show(Request $request)
    {
        $employeeId = $request->employeeId;
        try {
            $employee = User::findOrFail($employeeId); 

            return response()->json([
                'success' => true,
                'data' => $employee,
                'error' => null
            ], 201);
        } catch (\Throwable $th) { 
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
            ],400);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //
    }
}
