<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
class PayrollController extends Controller
{
    public function index(Request $request, $companyId) {
    
        $rawmonth = $request->query('month', Carbon::now()->format('Y-m')); // Default to current month if not provided
        $employees = User::select('firstname','lastname', 'id', 'avatar', 'email', 'company_id', 'wage', 'wage_rate')->where('company_id', $companyId)->get();
        [$year, $month] = explode('-', $rawmonth);
        $employeesWithHours = $employees->map(function ($employee) use ($year, $month, $rawmonth) {
            
            $payroll = Payroll::where('employee_id', $employee->id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->first();
        
            $employee->hours_worked = $employee->hoursWorked($rawmonth); 
            if ($payroll && $payroll->payslip_url) {
                // $employee->payroll_status = Storage::disk('local')->temporaryUrl($payroll->payslip_url, now()->addHours(24));
                $employee->payroll_status = asset($payroll->payslip_url);
            } else {
                $employee->payroll_status = null;
            }
            return $employee;
        });

        return response()->json([
            'success' => true,
            'data' => $employeesWithHours,
            'error' => null,
            'test' => [
                'month' => $month,
                'employees' => $employeesWithHours,
                'company_id' => $companyId
            ]
        ]);
    }
    // Get payroll for a specific user
    public function payrollByUserId($companyId, $employeeId)
    {
        $slipUrl = "";
        $allPayrols = [];
        $payroll = Payroll::where('employee_id', $employeeId)->get();
        // print_r($payroll);
        // die();
        foreach ($payroll as $p) {
            // $slipUrl = Storage::disk('local')->temporaryUrl($p->payslip_url, now()->addHours(24));
            $slipUrl = asset($p->payslip_url, true);
            $allPayrols[] =  array(...$p->toArray(), 'payslip_url' => $slipUrl);
        }
        
        return response()->json([
            'success' => true,
            'data' => $allPayrols,
            'error' => null, 
        ]);
    }

    // Add payroll for a user
    // public function addPayroll(Request $request, $companyId)
    // {
    //     $validated = $request->validate([
    //         'employee_id' => 'required|exists:users,id',
    //         'basic_salary' => 'required|numeric',
    //         'bonus' => 'nullable|numeric',
    //         'deduction' => 'nullable|numeric',
    //         'overtime_hours' => 'nullable|numeric',
    //         'overtime_pay' => 'nullable|numeric',
    //     ]);

    //     // Ensure user belongs to the company
    //     $employee = User::where('id', $request->employee_id)->where('company_id', $companyId)->first();
    //     if (!$employee) {
    //         return response()->json(['error' => 'Employee does not belong to this company.'], 404);
    //     }

    //     $totalSalary = $validated['basic_salary'] + ($validated['bonus'] ?? 0) - ($validated['deduction'] ?? 0) + (($validated['overtime_hours'] ?? 0) * ($validated['overtime_pay'] ?? 0));

    //     $payroll = Payroll::create(array_merge($validated, ['total_salary' => $totalSalary]));

    //     return response()->json($payroll);
    // }

    // Update payroll for a user
    public function updatePayroll(Request $request, $companyId, $payrollId)
    {
        $payroll = Payroll::where('id', $payrollId)
                          ->whereHas('employee', function ($query) use ($companyId) {
                              $query->where('company_id', $companyId);
                          })
                          ->first();

        if (!$payroll) {
            return response()->json(['error' => 'Payroll not found for this company.'], 404);
        }

        $validated = $request->validate([
            'basic_salary' => 'nullable|numeric',
            'bonus' => 'nullable|numeric',
            'deduction' => 'nullable|numeric',
            'overtime_hours' => 'nullable|numeric',
            'overtime_pay' => 'nullable|numeric',
        ]);

        $totalSalary = ($validated['basic_salary'] ?? $payroll->basic_salary)
            + ($validated['bonus'] ?? $payroll->bonus)
            - ($validated['deduction'] ?? $payroll->deduction)
            + (($validated['overtime_hours'] ?? $payroll->overtime_hours) * ($validated['overtime_pay'] ?? $payroll->overtime_pay));

        $payroll->update(array_merge($validated, ['total_salary' => $totalSalary]));

        return response()->json($payroll);
    }

    // Delete payroll for a user
    public function deletePayroll($companyId, $payrollId)
    {
        $payroll = Payroll::where('id', $payrollId)
                          ->whereHas('employee', function ($query) use ($companyId) {
                              $query->where('company_id', $companyId);
                          })
                          ->first();

        if (!$payroll) {
            return response()->json(['error' => 'Payroll not found for this company.'], 404);
        }

        $payroll->delete();
        return response()->json(['message' => 'Payroll deleted successfully.']);
    }

    // Generate payroll PDF and store it
    public function generatePayroll(Request $request, $companyId, $employeeId)
    {
        $validated = $request->validate([
            'bonus' => 'nullable|numeric',
            'deduction' => 'nullable|numeric',
        ]);

        $bonus = $validated['bonus'] ?? 0;
        $deduction = $validated['deduction'] ?? 0;

     
        $employee = User::where('id', $employeeId)
                        ->where('company_id', $companyId)
                        ->first();
    
        if (!$employee) {
            return response()->json(['error' => 'Employee not found for this company.'], 404);
        }
    
        $month = Carbon::now()->format('F Y');
        $today = Carbon::now()->format('Y-m-d');
        $hoursWorked = $employee->hoursWorked(Carbon::now()->format('Y-m'));
    
        $regularHours = $hoursWorked['hoursWorked'];
        $otHours = $hoursWorked['otHours'];
        $regularHoursformatted = $hoursWorked['hoursWorkedFormatted'];
        $otHoursformatted = $hoursWorked['otHoursFormatted'];
        $wage = (float)$employee->wage_rate;
    
        $regularPay = $regularHours * $wage;
        $otPay = $otHours * $wage * 1.2; // 20% more for overtime hours
    
        $subtotal = $regularPay + $otPay;
        $total = $subtotal + $bonus - $deduction;
    
        
        $payroll = Payroll::create([
            'employee_id' => $employeeId,
            'pay_rate' => $wage,
            'hours_worked' => $regularHoursformatted,
            'overtime_hours' => $otHoursformatted,
            'overtime_pay' => $otPay,
            'deduction' => $deduction,
            'bonus' => $bonus,
            'basic_salary' => $subtotal,
            'total_salary' => $total,
            'payslip_url' => null, // Placeholder for now
        ]);
    
      
    
        $pdf = Pdf::loadView('payroll.payslip', [
            'month' => $month,
            'today' => $today,
            'company' => $employee->company->name,
            'employeeId' => $employee->id,
            'employeeName' => $employee->firstname . ' ' . $employee->lastname,
            'hoursWorked' => $regularHoursformatted,
            'otHours' => $otHoursformatted,
            'holidays' => $hoursWorked['holidays'],
            'bonus' => '£' . number_format($bonus, 2), 
            'deduction' => '£' . number_format($deduction, 2), 
            'hourlyRate' => '£' . number_format($wage, 2),
            'subtotal' => '£' . number_format($subtotal, 2),
            'total' => '£' . number_format($total, 2)
        ]);
    
        $fileName = public_path('payslips/' . $payroll->id . '_payslip.pdf');
    
        if (!file_exists(public_path('payslips'))) {
            mkdir(public_path('payslips'), 0755, true);
        }
    
        $pdf->save($fileName);
        $url = asset('payslips/' . $payroll->id . '_payslip.pdf');
    
        $payroll->update(['payslip_url' => 'payslips/' . $payroll->id . '_payslip.pdf']);
        
        return response()->json([
            'success' => true,
            'data' => $url,
            'error' => null, 
        ]); 
    }
    

}
