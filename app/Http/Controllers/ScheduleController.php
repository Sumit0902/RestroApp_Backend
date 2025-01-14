<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules for a specific company.
     */
    

    public function index(Request $request) {
        $weekNumber = $request->week_number;
        $year = $request->year;
        $companyId = $request->company_id;

        $schedules = Schedule::where('company_id', $companyId)
            ->where('week_number', $weekNumber)
            ->where('year', $year)
            ->with(['user', 'shift'])
            ->get()
            ;

        $schedData = array();

        if($schedules) {
            foreach($schedules as $schedule) {
                $schedData[$schedule->user_id] = array(
                    'user' => $schedule->user->firstname.' '.$schedule->user->lastname,
                    'shift' => [
                        'name' => $schedule->shift->name,
                        'start_time' => $schedule->shift->start_time,
                        'end_time' => $schedule->shift->end_time
                    ],
                    'notes' => $schedule->notes,
                    'schedule' => $schedule->days_schedule,
                    'scheduleId' => $schedule->id
                );
            }
        }



            // $groupedSchedules = $schedules->groupBy('user_id')->map(function ($userSchedules) {
            //     $user = $userSchedules->first()->user;
            
            //     return [
            //         'user' => [
            //             'id' => $user->id,
            //             'firstname' => $user->firstname,
            //             'lastname' => $user->lastname,
            //         ],
            //         'schedules' => $userSchedules->map(function ($schedule) {
            //             $shift = $schedule->shift; // Assuming shift relationship exists
            //             return [
            //                 'day' => $schedule->days_schedule,
            //                 'schedule_id' => $schedule->id,
            //                 'shift' => [
            //                     'name' => $shift->name,
            //                     'start_time' => $shift->start_time,
            //                     'end_time' => $shift->end_time,
            //                 ],
            //             ];
            //         })->toArray(),
            //     ];
            // });

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
            'week_number' => 'nullable|integer',
            'selectedDays' => 'required|array',
            'selectedDays.*' => 'date',
        ]);
    
        try {
            $employeeId = $validatedData['employee'];
            $companyId = $validatedData['company_id'];
            $shiftId = $validatedData['shift'];
            $notes = $validatedData['notes'];
            $selectedDays = $validatedData['selectedDays'];
            $week_number = $validatedData['week_number'];
    
            // Initialize a schedule array to organize days by week and year
            $weeklySchedule = [];
    
            foreach ($selectedDays as $date) {
                $carbonDate = Carbon::parse($date);
                $weekNumber = $carbonDate->isoWeek(); // Get ISO week number
                $year = $carbonDate->year; // Get year
            
                // Use Carbon's dayOfWeek property for numeric keys (Monday = 0, Sunday = 6)
                $dayOfWeek = $carbonDate->dayOfWeekIso - 1; // Monday = 0, Tuesday = 1, ..., Sunday = 6
            
                // Add the day to the corresponding week and year
                $weeklySchedule[$year][$weekNumber][$dayOfWeek] = $shiftId;
            }
    
            // Insert or update schedules for each week and year
            foreach ($weeklySchedule as $year => $weeks) {
                foreach ($weeks as $weekNumber => $daysSchedule) {
                    // Check if a schedule already exists for this user, company, year, and week
                    $existingSchedule = Schedule::where('user_id', $employeeId)
                        ->where('company_id', $companyId)
                        ->where('year', $year)
                        ->where('week_number', $weekNumber)
                        ->first();
    
                    if ($existingSchedule) {
                        // Update the existing schedule
                        $existingSchedule->update([
                            'days_schedule' => json_encode($daysSchedule),
                            'notes' => $notes,
                        ]);
                    } else {
                        // Create a new schedule
                        Schedule::create([
                            'user_id' => $employeeId,
                            'company_id' => $companyId,
                            'shift_id' => $shiftId,
                            'week_number' => $weekNumber,
                            'year' => $year,
                            'days_schedule' => json_encode($daysSchedule),
                            'notes' => $notes,
                        ]);
                    }
                }
            }
    
            return response()->json([
                'success' => true,
                'data' => 'Schedules created/updated successfully',
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
     * Display a specific schedule.
     */
    public function show(Request $request)
    {
        $scheduleId = $request->query('scheduleId'); 

        return response()->json([
            'success' => false,
            'data' => $scheduleId, 
        ], 201);

        try {
            $schedule = Schedule::with(['user', 'shift'])->findOrFail($id);

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
