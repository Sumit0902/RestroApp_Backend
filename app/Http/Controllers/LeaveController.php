<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->companyId;

        try {
            $leaves = Leave::where('company_id', $companyId)
                        // ->orderByRaw("CASE 
                        //     WHEN status = 'pending' THEN 1
                        //     WHEN status = 'approved' THEN 2
                        //     WHEN status = 'denied' THEN 3
                        //     ELSE 4 END")
                        ->orderBy('created_at', 'desc')
                        ->with('user')
                        ->get();
            
            return response()->json([
                'success' => true,
                'data' => $leaves,
                'error' => null
            ], 200); 
        } catch (\Throwable $th) {
            return response()->json([   
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
                'dd' => $request
            ], 400);
        }
    }
    public function UserLeaves(Request $request)
    {
        $userId = $request->employeeId;

        try {
            $leaves = Leave::where('user_id', $userId)
                        ->orderBy('start_date', 'desc')
                        ->get();
            
            return response()->json([
                'success' => true,
                'data' => $leaves,
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

    public function store(Request $request)
    {
        $userId = $request->employeeId;
        $companyId = $request->companyId;
        // $request->validate([
        //     'type' => 'required|string',
        //     'start_date' => 'required|date',
        //     'end_date' => 'required|date|after_or_equal:start_date',
        //     'reason' => 'nullable|string',
        // ]);

        try {
            $leave = Leave::create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'type' => $request->leaveType,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);
            $dateRange = ($request->start_date == $request->end_date) ? 'for '.$request->start_date : 'from '.$request->start_date . ' to ' . $request->end_date;
            // $message = "Leave request from {$request->firstname} {$request->lastname} for {$dateRange} is pending";
            $message = "{$request->firstname} {$request->lastname} just requested a leave  {$dateRange}";
            NotificationService::createNotification($message, $userId, null, $companyId );
            return response()->json([
                'success' => true,
                'data' => $leave,
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

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $leave = Leave::findOrFail($id);
        $leave->status = $request->status;
        $leave->save();

        return response()->json([
            'success' => true,
            'data' => "Leave status updated successfully",
            'error' => null
        ], 200); 
    }

    public function approve(Request $request)
    {
        try {
            $leave = Leave::findOrFail($request->leave_id);
    
            if($leave) {
    
                $leave->status = 'approved';
                $leave->remarks = $request->remarks;
                $leave->save();
                
                NotificationService::createNotification("A Manager approved your leave request.", Auth::user()->id, $leave->user_id, $leave->company_id );
                return response()->json([
                    'success' => true,
                    'data' => 'Leave approved successfully',
                    'error' => null
                ], 200); 
    
            }
            else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => 'Invalid leave ID',
                ], 400);
            }
            //code...
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }
    public function reject(Request $request)
    {
        try {
            $leave = Leave::findOrFail($request->leave_id);
    
            if($leave) {
    
                $leave->status =  'rejected';
                $leave->remarks = $request->remarks;
                $leave->save();
                
                NotificationService::createNotification("A Manager rejected your leave request.", Auth::user()->id, $leave->user_id, $leave->company_id );
                return response()->json([
                    'success' => true,
                    'data' => 'Leave approved successfully',
                    'error' => null
                ], 200); 
    
            }
            else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => 'Invalid leave ID',
                ], 400);
            }
            //code...
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }
    public function cancel(Request $request)
    {
        try {
            $leave = Leave::findOrFail($request->leave_id);
    
            if($leave) {
                $leave->delete();
                
                // NotificationService::createNotification("A Manager rejected your leave request.", Auth::user()->id, $leave->user_id, $leave->company_id );
                return response()->json([
                    'success' => true,
                    'data' => 'Leave canceled successfully',
                    'error' => null
                ], 200); 
    
            }
            else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => 'Invalid leave ID',
                ], 400);
            }
            //code...
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
    }
}
