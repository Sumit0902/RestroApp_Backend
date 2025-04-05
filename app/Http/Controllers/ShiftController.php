<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;

class ShiftController extends Controller
{
    /**
     * Get all shifts for a company.
     */
    public function index(Request $request)
    {
        $companyId = $request->companyId;

        try {
            $shifts = Shift::where('company_id', $companyId)->get();

            return response()->json([
                'success' => true,
                'data' => $shifts,
                'error' => null
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }

    /**
     * Store a new shift.
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
        ]);
        

        try {
            $shift = Shift::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $shift,
                'error' => null
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
                'req' => $request->all()
            ], 400);
        }
    }

    /**
     * Show a specific shift.
     */
    public function show($id)
    {
        try {
            $shift = Shift::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $shift,
                'error' => null
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 404);
        }
    }

    /**
     * Update a shift.
     */
    public function update(Request $request, $companyId, $shiftId)
    {
        $rv = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
        ]);

       

        try {
            $shift = Shift::findOrFail($shiftId);
            $shift->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $shift,
                'error' => null
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a shift.
     */
    public function destroy($id)
    {
        try {
            $shift = Shift::findOrFail($id);
            $shift->delete();

            return response()->json([
                'success' => true,
                'data' => 'Shift deleted successfully',
                'error' => null
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }
}
