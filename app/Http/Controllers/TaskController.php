<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $message = "You have a new task assigned to you: " . $task->name;
        NotificationService::createNotification($message, null, $validated['user_id'], $companyId );
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
            'status' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $task = Task::where('id', $taskId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        // Store the original values before updating
        $originalUserId = $task->user_id;

        // Update the task with the new values
        $task->update($request->only([
            'name',
            'description',
            'status',
            'user_id',
        ]));

        // Check if the logged-in user is a manager
        $loggedInUser = Auth::user();
        $isManager = $loggedInUser->role === 'manager';

        // Handle notifications based on the update
        if ($isManager) {
            if ($request->has('name') || $request->has('description')) {
                // Notify the current user assigned to the task
                $message = "The task you assigned has an update: " . $task->name;
                NotificationService::createNotification($message, null, $task->user_id, $companyId);
            }

            if ($request->has('user_id') && $originalUserId != $task->user_id) {
                // Notify the previous user
                $prevUserMessage = "Your task has been assigned to someone else. Task name: " . $task->name;
                NotificationService::createNotification($prevUserMessage, null, $originalUserId, $companyId);

                // Notify the new user
                $nextUserMessage = "You have been assigned a task: " . $task->name;
                NotificationService::createNotification($nextUserMessage, null, $task->user_id, $companyId);
            }
        } 
        else {
            if($request->status  == 'completed'){
                $message = "The task has been marked as completed: " . $task->name;
                NotificationService::createNotification($message, $task->user_id, null, $companyId);
            } 
            else {
                $nextUserMessage = "Task: " . $task->name. "has been updated.";
                NotificationService::createNotification($nextUserMessage, $task->user_id, null, $companyId);
            }
        }

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
