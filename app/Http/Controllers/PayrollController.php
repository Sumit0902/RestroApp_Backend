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
    
        $month = $request->query('month', Carbon::now()->format('Y-m')); // Default to current month if not provided
        $employees = User::where('company_id', $companyId)->get();

        $employeesWithHours = $employees->map(function ($employee) use ($month) {
            
            $payroll = Payroll::where('employee_id', $employee->id)->first();
        
            $employee->hours_worked = $employee->hoursWorked($month); 
            if ($payroll && $payroll->payslip_url) {
                $employee->payroll_status = Storage::disk('local')->temporaryUrl($payroll->payslip_url, now()->addHours(24));
            } else {
                $employee->payroll_status = null;
            }
            return $employee;
        });

        return response()->json([
            'success' => true,
            'data' => $employeesWithHours,
            'error' => null,
            'test' => 'dfdf'
        ]);
    }
    // Get payroll for a specific user
    public function payrollByUserId($companyId, $userId)
    {
        $payroll = Payroll::where('employee_id', $userId)
                          ->whereHas('employee', function ($query) use ($companyId) {
                              $query->where('company_id', $companyId);
                          })
                          ->get();

        return response()->json($payroll);
    }

    // Add payroll for a user
    public function addPayroll(Request $request, $companyId)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'basic_salary' => 'required|numeric',
            'bonus' => 'nullable|numeric',
            'deduction' => 'nullable|numeric',
            'overtime_hours' => 'nullable|numeric',
            'overtime_pay' => 'nullable|numeric',
        ]);

        // Ensure user belongs to the company
        $employee = User::where('id', $request->employee_id)->where('company_id', $companyId)->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee does not belong to this company.'], 404);
        }

        $totalSalary = $validated['basic_salary'] + ($validated['bonus'] ?? 0) - ($validated['deduction'] ?? 0) + (($validated['overtime_hours'] ?? 0) * ($validated['overtime_pay'] ?? 0));

        $payroll = Payroll::create(array_merge($validated, ['total_salary' => $totalSalary]));

        return response()->json($payroll);
    }

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
    public function generatePayroll($companyId, $employeeId)
    {
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
        $wage = (float)$employee->wage_rate;

        $regularPay = $regularHours * $wage;
        $otPay = $otHours * $wage * 1.2; // 20% more for overtime hours

        $subtotal = $regularPay + $otPay;
        $bonus = 0; // Set default values or calculate as needed
        $deduction = 0; // Set default values or calculate as needed
        $total = $subtotal + $bonus - $deduction;

        $payroll = Payroll::create([
            'employee_id' => $employeeId,
            'basic_salary' => $subtotal, // Set default values or calculate as needed
            'bonus' => $bonus,
            'deduction' => $deduction,
            'overtime_hours' => $otHours,
            'overtime_pay' => $otPay, // Set default values or calculate as needed
            'total_salary' => $total, // Set default values or calculate as needed
        ]);

        $pdf = Pdf::loadView('payroll.payslip', [
            'month' => $month,
            'today' => $today,
            'company' => $employee->company->name,
            'employeeId' => $employee->id,
            'employeeName' => $employee->firstname . ' ' . $employee->lastname,
            'hoursWorked' => $regularHours,
            'otHours' => $otHours,
            'holidays' => $hoursWorked['holidays'],
            'bonus' => '£' . number_format($bonus, 2),
            'bonusReason' => '', // Add bonus reason if available
            'deduction' => '£' . number_format($deduction, 2),
            'deductionReason' => '', // Add deduction reason if available
            'hourlyRate' => '£' . number_format($wage, 2),
            'subtotal' => '£' . number_format($subtotal, 2),
            'total' => '£' . number_format($total, 2)
        ]);
        $fileName = 'payslips/' . $payroll->id . '_payslip.pdf';
        Storage::put($fileName, $pdf->output());

        // Store the URL in the database
        $url = Storage::url($fileName);
        $payroll->update(['payslip_url' => $fileName]);

        return response()->json(['url' => $url]);
    }
}
