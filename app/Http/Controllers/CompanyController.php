<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

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
            $companyData = $request->except('logo'); // Get all the data except the logo
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
                $companyData['logo'] = $logoPath; // Add the logo path to the company data
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
            // Handle avatar upload if provided
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
}
