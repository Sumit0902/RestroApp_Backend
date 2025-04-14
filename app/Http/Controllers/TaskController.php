<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function index(Request $request,  $companyId)
    {
       
        try {
            $tasks = Task::where('company_id', $companyId)->with('user')->get();
    
            return response()->json([
                'success' => true,
                'data' => $tasks,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
            ],400);
        }
    }

    public function show(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $task = Task::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function store(Request $request, $companyId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id', 
            'name' => 'required|string|max:255',
            'description' => 'nullable|string', 
            'status' => 'nullable|string', 
        ]);

        $task = Task::create(array(
            'name' => $validated['name'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'user_id' => $validated['user_id'],
            'company_id' => $companyId,
        ));

        return response()->json([
            'success' => true,
            'task' => $task,
        ], 201);
    }

    public function update(Request $request, $companyId, $taskId)
    {
        $request->validate([ 
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'weekdays' => 'nullable|array',
            'weekdays.*' => 'integer|min:0|max:6',
        ]);

        $task = Task::where('id', $taskId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $task->update($request->only([
            // 'name',
            // 'description',
            // 'is_recurring',
            'status',
        ]));

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function destroy(Request $request, $companyId, $taskId)
    {
        try {
            $task = Task::where('id', $taskId)
                ->where('company_id', $companyId)
                ->firstOrFail();
    
            $task->delete();
    
            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ]);
        }

    }

    public function getEmployeeTasks(Request $request, $companyId, $employeeId)
    {
        try {
            // Fetch tasks for the given company and employee
            $tasks = Task::where('company_id', $companyId)
                ->where('user_id', $employeeId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tasks,
            ], 200);
        } catch (\Throwable $th) {
            // Handle any errors
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 400);
        }
    }

    public function getEmployeeWeeklyTasks(Request $request, $companyId, $employeeId)
    {
        $request->validate([
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
        ]);

        try {
            // Adjust the week_end to include the full day
            $weekStart = $request->week_start;
            $weekEnd = Carbon::parse($request->week_end)->endOfDay(); // Set time to 23:59:59

            // Fetch tasks for the given company, employee, and date range
            $tasks = Task::where('company_id', $companyId)
                ->where('user_id', $employeeId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tasks,
            ], 200);
        } catch (\Throwable $th) {
            // Handle any errors
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 400);
        }
    }

    public function getCompanyWeeklyTasks(Request $request, $companyId)
    {
        $request->validate([
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
        ]);

        try {
            // Adjust the week_end to include the full day
            $weekStart = $request->week_start;
            $weekEnd = Carbon::parse($request->week_end)->endOfDay(); // Set time to 23:59:59

            // Fetch tasks for the given company, employee, and date range
            $tasks = Task::where('company_id', $companyId) 
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->with(['user' => function ($query) {
                    $query->select('id', 'firstname', 'lastname', 'email', 'role', 'phone', 'company_id', 'avatar');
                }]) 
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tasks,
            ], 200);
        } catch (\Throwable $th) {
            // Handle any errors
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 400);
        }
    }
}
