<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $allcompany = Company::with('employees')->get();

        return response()->json([
            'success' => true,
            'data' => $allcompany,
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {   
        try {
            $companyData = $request->except('logo'); 
            $companyData['logo'] = null;
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
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
    
                $randomString = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
                $filename = 'logo_' . $randomString . '.' . $extension;
                $logoPath = $file->storeAs('', $filename, 'public');
                $companyData['logo'] = $logoPath; 
            }

            $company = Company::create($companyData);


            return response()->json([
                'success' => true,
                'data' => $company,
                'error' => null
            ],201);
            //code...
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
            ], 400);
        } 
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $companyId = $request->companyId;
        try {
            $company = Company::with('employees')->findOrFail($companyId); 

            return response()->json([
                'success' => true,
                'data' => $company,
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
         
        try {
            $company = Company::findOrFail($request->companyId);


            $companyData = $request->except('logo');
            $companyData['avatar'] = null; 
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
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
                $randomString = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
      
                $originalFilename = pathinfo($request->file('logo')->getClientOriginalName(), PATHINFO_FILENAME);
 
                $sanitizedFilename = preg_replace('/[^A-Za-z0-9\-.]+/', '_', $originalFilename);
 
                $filename = $sanitizedFilename . '_' . $randomString . '.' . $extension; 
                $logoPath = $file->storeAs('', $filename, 'public');
                $companyData['logo'] = $logoPath; // Add the logo path to the company data
                $company->logo = $companyData['logo'];
            } 
    
          
            $company->company_name = $companyData['company_name'];
            $company->company_about = $companyData['company_about'];
            $company->company_address1 = $companyData['company_address1'];
            $company->company_address2 = $companyData['company_address2'];
            $company->company_city = $companyData['company_city'];
            $company->company_state = $companyData['company_state'];
            $company->company_zip = $companyData['company_zip'];
            $company->phone = $companyData['phone'];
            $company->workingDays = $companyData['workingDays'];
            $company->email = $companyData['email'];
            $company->ot_rate = $companyData['ot_rate'];
            $company->ot_type = $companyData['ot_type'];
            

            $company->save();

            return response()->json([
                'success' => true,
                'data' => $company,
                'error' => null, 
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
                'dd' => $request->all()
            ], 400);
        } 
       
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //
    }

    public function companyEmployees(Request $request)
    {
        $companyId = $request->companyId;
        try {
            $company = Company::with('employees')->findOrFail($companyId); 

            return response()->json([
                'success' => true,
                'data' => $company,
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

    public function companyRegistration(Request $request)
    {
        $request->validate([
            // Company fields
            'company_name' => 'required|string|max:255',
            'company_about' => 'nullable|string',
            'company_address1' => 'required|string|max:255',
            'company_address2' => 'nullable|string|max:255',
            'company_city' => 'required|string|max:255',
            'company_state' => 'required|string|max:255',
            'company_zip' => 'required|string|max:10',
            'phone' => 'required|string|max:25',
            'email' => 'required|email|unique:companies,email',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'manager_email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            // 'manager_phone' => 'required|string|max:15',
            'role' => 'required|string|in:manager',
        ]);

        try {
            $company = Company::create([
                'company_name' => $request->company_name,
                'company_about' => $request->company_about,
                'company_address1' => $request->company_address1,
                'company_address2' => $request->company_address2,
                'company_city' => $request->company_city,
                'company_state' => $request->company_state,
                'company_zip' => $request->company_zip,
                'phone' => $request->phone,
                'email' => $request->email,
            ]);

            $manager = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->manager_email,
                'password' => Hash::make($request->password),
                'phone' => $request->manager_phone,
                'role' => 'manager', //  as this is company registration and only manager can do that
                'company_id' => $company->id, 
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Company and manager created successfully.',
                'data' => [
                    'company' => $company,
                    'manager' => $manager,
                ],
            ], 201);
        } catch (\Throwable $th) { 
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration.',
                'error' => $th->getMessage(),
            ], 400);
        }
    }
}
