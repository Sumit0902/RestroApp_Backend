<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TimeSheet;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\UserCheckIn;
use Illuminate\Support\Facades\DB;
class TimeSheetController extends Controller
{
    // List all timesheet entries for a company and an employee
    public function index(Request $request)
    {
        $companyId = $request->companyId;
        $month = $request->month; 
        
        [$year, $month] = explode('-', $month);
        if ($companyId != null) {

            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            
            $timesheets = TimeSheet::where('company_id', $companyId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->orderBy('check_in', 'asc')
                ->with('user')
                ->get();

            
            $users = User::select('*')->where('company_id', $companyId)->get();
    
            $attendanceRaw = TimeSheet::where('company_id', $companyId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->whereIn('user_id', $users->pluck('id'))
                ->get(['user_id',  'check_in', 'check_out']);


                $attendanceByUser = [];
                foreach ($attendanceRaw as $record) {
                    $date = Carbon::parse($record->date)->toDateString(); // Use 'date' column, not 'created_at'
                    $attendanceByUser[$record->user_id][$date] = [
                        'check_in' => $record->check_in ? Carbon::parse($record->check_in)->toTimeString() : null,
                        'check_out' => $record->check_out ? Carbon::parse($record->check_out)->toTimeString() : null,
                    ];
                }
        
                // Build the response
                $timesheets = $users->map(function ($user) use ($attendanceByUser) {
                    return [
                        'user' =>  $user,
                        'attendance' => $attendanceByUser[$user->id] ?? [],
                    ];
                });
        
        } 


        return response()->json([
            'success' => true,
            'timesheets' => $timesheets,
        ]);
    }

    // Check-in logic
    public function checkIn(Request $request)
    {
        $employeeId = $request->employee_id;
        $companyId = $request->company_id;
        $user = User::where('id', $employeeId)->first();
        // Get today's date in 'Y-m-d' format
        $today = now()->format('Y-m-d');
    
        // Check if a timesheet exists for today's date
        $existingTimesheet = TimeSheet::where('company_id', $companyId)
            ->where('user_id', $employeeId)
            ->whereDate('check_in', $today)
            ->first();
    
        if ($existingTimesheet) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in for today already exists.',
            ], 400); // Bad Request
        }
    
        try {
            $newTimesheet = TimeSheet::create([
                'company_id' => $companyId,
                'user_id' => $employeeId,
                'check_in' => now(),
            ]);

            $currentTime = now()->format('h:iA');
            $message = "{$user->firstname} {$user->firstname} just checked in @ {$currentTime}";
            
            NotificationService::createNotification($message, $employeeId, null, $companyId );
            
            return response()->json([
                'success' => true,
                'message' => 'Check-in created successfully.',
                'timesheet' => $newTimesheet,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 400); // Bad Request
        }
        
    }

    // Check-out logic
    public function checkOut(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'employee_id' => 'required|exists:users,id',
        ]);

        $employeeId = $request->employee_id;
        $companyId = $request->company_id;

        try {
            $timesheet = TimeSheet::where('company_id', $companyId)
                ->where('user_id', $employeeId)
                ->whereNull('check_out')
                ->whereDate('check_in', '=', now()->toDateString())
                ->latest('check_in')
                ->first();
    
            if(!$timesheet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-out for today already exists.',
                ], 400); // Bad Request
            }
            $timesheet->update(['check_out' => now()]);
    
            return response()->json([
                'success' => true,
                'timesheet' => $timesheet,
            ],201);
            //code...
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 400); // Bad Requestw
        }
    }

    // Remove check-out from a timesheet (reset check-out)
    public function resetCheckOut(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $timesheet = TimeSheet::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        $timesheet->update(['check_out' => null]);

        return response()->json([
            'success' => true,
            'timesheet' => $timesheet,
        ]);
    }

    // Fetch a single timesheet entry by ID
    public function show(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $timesheet = TimeSheet::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'timesheet' => $timesheet,
        ]);
    }

    public function getTimesheetForEmployee(Request $request){   
        
        $employeeId = $request->employeeId;
        $companyId = $request->companyId;
        $month = $request->month; // Format: 'YYYY-MM'

        // Ensure the authenticated user can only fetch their own timesheets
        if (Auth::user()->id != intval($employeeId)) {
            return response()->json([
                'success' => false,
                'msg' => "You are not authorized to perform this action",
            ], 403);
        }

        // Extract year and month
        [$year, $month] = explode('-', $month);

        // Retrieve all timesheets for the specified month and year
        $timesheets = TimeSheet::where('company_id', $companyId)
            ->where('user_id', $employeeId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('check_in', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'timesheets' => $timesheets,
        ]);
    }
}
