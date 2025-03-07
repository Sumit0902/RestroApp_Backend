<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
class EmployeeController extends Controller
{
    public function index(Request $request, $companyId)
    {
        $month = $request->query('month', Carbon::now()->format('Y-m')); // Default to current month if not provided
        $employees = User::where('company_id', $companyId)->get();

        $employeesWithHours = $employees->map(function ($employee) use ($month) {
            $employee->hours_worked = $employee->hoursWorked($month);
            return $employee;
        });

        return response()->json([
            'success' => true,
            'data' => $employeesWithHours,
            'error' => null,
            'test' => 'dfdf'
        ]);
    }

    public function store(Request $request, $companyId)
    {
        $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:15',
            'role' => 'required|string|in:employee,manager',
            'company_role' => 'nullable|string|max:255',
            'wage' => 'nullable|numeric',
            'wage_rate' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $validatedData['company_id'] = $companyId;
        $validatedData['password'] = bcrypt($validatedData['password']);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('avatars'), $filename);
            $validatedData['avatar'] = 'avatars/' . $filename;
        }

        $employee = User::create($validatedData);

        return response()->json([
            'success' => true,
            'data' => $employee,
            'error' => null
        ], 201);
    }

    public function show($companyId, $employeeId)
    {
        $employee = User::where('company_id', $companyId)->findOrFail($employeeId);

        return response()->json([
            'success' => true,
            'data' => $employee,
            'error' => null
        ]);
    }

    public function update(Request $request, $companyId, $employeeId)
    {
        $employee = User::where('company_id', $companyId)->findOrFail($employeeId);

        $validatedData = $request->validate([
            'firstname' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $employeeId,
            'password' => 'sometimes|string|min:8',
            'phone' => 'nullable|string|max:15',
            'role' => 'sometimes|string|in:employee,manager',
            'company_role' => 'nullable|string|max:255',
            'wage' => 'nullable|numeric',
            'wage_rate' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('avatars'), $filename);
            $validatedData['avatar'] = 'avatars/' . $filename;
        }

        if (isset($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }

        $employee->update($validatedData);

        return response()->json([
            'success' => true,
            'data' => $employee,
            'error' => null
        ]);
    }

    public function destroy($companyId, $employeeId)
    {
        $employee = User::where('company_id', $companyId)->findOrFail($employeeId);
        $employee->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'error' => null
        ]);
    }
}
