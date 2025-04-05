<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'weekdays' => 'nullable|array',
            'weekdays.*' => 'integer|min:0|max:6',
        ]);

        $task = Task::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        $task->update($request->only([
            'name',
            'description',
            'is_recurring',
            'weekdays',
        ]));

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $task = Task::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }
}
