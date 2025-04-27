<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Schedule;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;
class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules for a specific company.
     */
    

    public function index(Request $request)
    {
        $weekNumber = $request->week_number;
        $year = $request->year;
        $companyId = $request->company_id;

        $schedules = Schedule::where('company_id', $companyId)
            ->where('week_number', $weekNumber)
            ->where('year', $year)
            ->with(['user', 'shift'])
            ->get();

        $schedData = [];

        if ($schedules) {
            foreach ($schedules as $schedule) {
                $userId = $schedule->user_id;

                // If the user already exists in the array, append the shift and days_schedule
                if (isset($schedData[$userId])) {
                    $schedData[$userId]['shifts'][] = [
                        'name' => $schedule->shift->name,
                        'start_time' => $schedule->shift->start_time,
                        'end_time' => $schedule->shift->end_time,
                        'date' => $schedule->days_schedule,
                    ];
                } else {
                    // Create a new entry for the user
                    $schedData[$userId] = [
                        'userid' => $schedule->user->id,
                        'user' => $schedule->user->firstname . ' ' . $schedule->user->lastname,
                        'shifts' => [[
                            'name' => $schedule->shift->name,
                            'start_time' => $schedule->shift->start_time,
                            'end_time' => $schedule->shift->end_time,
                            'date' => $schedule->days_schedule,
                        ]],
                        'notes' => $schedule->notes,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $schedData,
            'error' => null,
        ]);
    }

    /**
     * Store a newly created schedule in the database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee' => 'required|exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'shift' => 'required|exists:shifts,id',
            'notes' => 'nullable|string',
            'selectedDays' => 'required|array',
            'selectedDays.*' => 'date',
        ]);

        try {
            $employeeId = $validatedData['employee'];
            $companyId = $validatedData['company_id'];
            $shiftId = $validatedData['shift'];
            $notes = $validatedData['notes'];
            $selectedDays = $validatedData['selectedDays'];

            foreach ($selectedDays as $date) {
                $carbonDate = Carbon::parse($date);
                $year = $carbonDate->year;
                $weekNumber = $carbonDate->isoWeek();

                // Check if a schedule already exists for this user, company, and date
                $existingSchedule = Schedule::where('user_id', $employeeId)
                    ->where('company_id', $companyId)
                    ->where('year', $year)
                    ->where('week_number', $weekNumber)
                    ->where('days_schedule', $carbonDate->toDateString())
                    ->first();

                if (!$existingSchedule) {
                    // Create a new schedule for the specific day
                    Schedule::create([
                        'user_id' => $employeeId,
                        'company_id' => $companyId,
                        'shift_id' => $shiftId,
                        'week_number' => $weekNumber,
                        'year' => $year,
                        'days_schedule' => $carbonDate->toDateString(), // Store only the date as text
                        'notes' => $notes,
                    ]);

                    $message = "A schedule has been created for you on " . $carbonDate->toDateString();
                    NotificationService::createNotification($message, null, $employeeId, $companyId );
                }
            }

            return response()->json([
                'success' => true,
                'data' => 'Schedules created successfully for new dates.',
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
                'errorf' => $th,
            ], 400);
        }
    }
    /**
     * Display a specific schedule.
     */
    public function show(Request $request)
    {
        $scheduleId = $request->query('scheduleId'); 

        // return response()->json([
        //     'success' => false,
        //     'data' => $scheduleId, 
        // ], 201);

        try {
            $schedule = Schedule::with(['user', 'shift'])->findOrFail($scheduleId);

            return response()->json([
                'success' => true,
                'data' => $schedule,
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 404);
        }
    }

    public function getSchedule($companyId, $scheduleId)
    {
        try {
            // Validate if the company exists
            $company = Company::findOrFail($companyId);

            // Retrieve the schedule with related employee and shift data
            $schedule = Schedule::with(['user', 'shift'])
                ->where('id', $scheduleId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            // Format selected days
            $selectedDays = json_decode($schedule->days_schedule, true) ?? [];

            // Prepare the response data
            $scheduleData = [
                'employee' => [
                    'id' => $schedule->user->id,
                    'firstname' => $schedule->user->firstname,
                    'lastname' => $schedule->user->lastname,
                ],
                'shift' => [
                    'id' => $schedule->shift->id,
                    'name' => $schedule->shift->name,
                    'start_time' => $schedule->shift->start_time,
                    'end_time' => $schedule->shift->end_time,
                ],
                'notes' => $schedule->notes,
                'selectedDays' => array_keys($selectedDays), // Convert keys to an array of days
            ];

            return response()->json($scheduleData, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Schedule or Company not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch schedule data'.$e->getMessage()], 500);
        }
    }
    public function getEmployeeSchedule(Request $request, $companyId, $employeeId)
    {
        try {
            $weekNumber = $request->week_number;
            $year = $request->year;
       
    
            $schedules = Schedule::where('company_id', $companyId)
                ->where('week_number', $weekNumber)
                ->where('year', $year)
                ->where('user_id', $employeeId)
                ->with(['user', 'shift'])
                ->get();
    
            $schedData = [];
    
            if ($schedules) {
                foreach ($schedules as $schedule) {
                    $userId = $schedule->user_id;

                    if (isset($schedData[$userId])) {
                        $schedData[$userId]['shifts'][] = [
                            'name' => $schedule->shift->name,
                            'start_time' => $schedule->shift->start_time,
                            'end_time' => $schedule->shift->end_time,
                            'date' => $schedule->days_schedule,
                        ];
                    } else {
                        // Create a new entry for the user
                        $schedData[$userId] = [
                            'userid' => $schedule->user->id,
                            'user' => $schedule->user->firstname . ' ' . $schedule->user->lastname,
                            'shifts' => [[
                                'name' => $schedule->shift->name,
                                'start_time' => $schedule->shift->start_time,
                                'end_time' => $schedule->shift->end_time,
                                'date' => $schedule->days_schedule,
                            ]],
                            'notes' => $schedule->notes,
                        ];
                    }
                }
            }
    
            return response()->json([
                'success' => true,
                'data' => $schedData,
                'rd' => [
                    'week_number' => $weekNumber,
                    'year' => $year,
                    'company_id' => $companyId,
                    'employee_id' => $employeeId,
                ],
                'error' => null,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Schedule or Company not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch schedule data'.$e->getMessage()], 400);
        }
    }

    /**
     * Update a specific schedule.
     */
    public function update(Request $request)
    {
        $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'user_id' => 'sometimes|exists:users,id',
            'date' => 'sometimes|date',
            'shift_id' => 'sometimes|exists:shifts,id',
            'notes' => 'nullable|string',
        ]);

        $scheduleId = $request->schedule_id;

        try {
            $schedule = Schedule::findOrFail($scheduleId);

            // Ensure the schedule belongs to the same company (optional check)
            if ($request->has('company_id') && $schedule->company_id != $request->company_id) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => 'Schedule does not belong to the specified company.',
                ], 403);
            }

            $schedule->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $schedule,
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a specific schedule.
     */
    public function destroy($id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->delete();

            return response()->json([
                'success' => true,
                'data' => null,
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }
}
