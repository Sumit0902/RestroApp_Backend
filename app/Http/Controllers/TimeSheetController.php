<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Shift;
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

        if ($companyId) {
            // Define month boundaries
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            // Fetch users of the company
            $users = User::where('company_id', $companyId)->where('role', 'employee')->get();

            // Fetch timesheets within the month range
            $attendanceRaw = TimeSheet::where('company_id', $companyId)
                ->whereBetween('check_in', [$startOfMonth, $endOfMonth]) // Use check-in instead of created_at
                ->whereIn('user_id', $users->pluck('id'))
                ->get(['user_id', 'check_in', 'check_out']);

            // Organize attendance by user
            $attendanceByUser = [];
            foreach ($attendanceRaw as $record) {
                $date = Carbon::parse($record->check_in)->toDateString(); // Convert check-in date to a string

                // Ensure array is initialized for the user
                if (!isset($attendanceByUser[$record->user_id])) {
                    $attendanceByUser[$record->user_id] = [];
                }

                // Assign check-in and check-out times
                $attendanceByUser[$record->user_id][$date] = [
                    'check_in' => $record->check_in ? Carbon::parse($record->check_in)->toTimeString() : null,
                    'check_out' => $record->check_out ? Carbon::parse($record->check_out)->toTimeString() : null,
                ];
            }

            // Format response data
            $timesheets = $users->map(function ($user) use ($attendanceByUser) {
                return [
                    'user' => [
                        'id' => $user->id,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'role' => $user->role,
                        'company_id' => $user->company_id,
                        'wage' => $user->wage,
                        'wage_rate' => $user->wage_rate,
                        'avatar' => $user->avatar,
                    ],
                    'attendance' => $attendanceByUser[$user->id] ?? [],
                ];
            });
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Invalid company ID.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'timesheets' => $timesheets,
        ]);
    }

    public function checkIn(Request $request) {
        $employeeId = $request->employee_id;
        $companyId = $request->company_id;
        $user = User::where('id', $employeeId)->first();
        $today = now()->format('Y-m-d');
        $currentTime = now()->format('h:i A'); // Convert to AM/PM format

        // Check if there's a schedule for today
        $schedule = Schedule::where('company_id', $companyId)
            ->where('user_id', $employeeId)
            ->whereDate('days_schedule', $today)
            ->first();

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'No schedule found for today. Check-in not allowed.',
            ], 400);
        }

        // Get the shift associated with the schedule
        $shift = Shift::where('id', $schedule->shift_id)->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift details not found for today.',
            ], 400);
        }

        // Convert shift times to AM/PM format
        $shiftStart = Carbon::parse($shift->start_time)->format('h:i A');
        $shiftEnd = Carbon::parse($shift->end_time)->format('h:i A');

        // Check if current time is within the shift time
        if ($currentTime < $shiftStart || $currentTime > $shiftEnd) {
            return response()->json([
                'success' => false,
                'message' => "Check-in not allowed outside of shift hours ($shiftStart - $shiftEnd).",
            ], 400);
        }

        // Check if a timesheet already exists for today
        $existingTimesheet = TimeSheet::where('company_id', $companyId)
            ->where('user_id', $employeeId)
            ->whereDate('check_in', $today)
            ->first();

        if ($existingTimesheet) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in for today already exists.',
            ], 400);
        }

        try {
            // Create new timesheet entry
            $newTimesheet = TimeSheet::create([
                'company_id' => $companyId,
                'user_id' => $employeeId,
                'check_in' => now(),
            ]);

            $message = "{$user->firstname} {$user->lastname} just checked in @ {$currentTime}";

            NotificationService::createNotification($message, $employeeId, null, $companyId);

            return response()->json([
                'success' => true,
                'message' => 'Check-in created successfully.',
                'timesheet' => $newTimesheet,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 400);
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
                ->whereDate('check_in', '=', now()->toDateString())
                ->latest('check_in')
                ->first();
    
            if (!$timesheet) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active check-in found for today.',
                ], 400); 
            }

            // Check if check-out already exists
            if ($timesheet->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-out for today already exists.',
                ], 400); 
            }

            // Check if 8 hours have passed since check-in
            $checkInTime = Carbon::parse($timesheet->check_in);
            $currentTime = now();
            $hoursPassed = $checkInTime->diffInHours($currentTime);

            if ($hoursPassed < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have to checkout after 8 hours.',
                ], 400); 
            }

            // Update the timesheet with the check-out time
            $timesheet->update(['check_out' => $currentTime]);
            $user = User::where('id', $employeeId)->first();
            $currentTime = now()->format('h:iA');
            $message = "{$user->firstname} {$user->lastname} just checked out @ {$currentTime}";

            NotificationService::createNotification($message, $employeeId, null, $companyId );
            return response()->json([
                'success' => true,
                'timesheet' => $timesheet,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 400); 
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

    public function getTimesheetForEmployee(Request $request)
    {
        try {
            $employeeId = Auth::user()->id;

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
                ->where(function ($query) use ($year, $month) {
                    $query->whereMonth('check_in', $month)
                          ->whereYear('check_in', $year);
                })
                ->orWhere(function ($query) use ($year, $month) {
                    $query->whereMonth('check_out', $month)
                          ->whereYear('check_out', $year);
                })
                ->get();

            return response()->json([
                'success' => true,
                'timesheets' => $timesheets,
                'data' => [
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'month' => $month,
                    'year' => $year,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 400);
        }
    }
}
