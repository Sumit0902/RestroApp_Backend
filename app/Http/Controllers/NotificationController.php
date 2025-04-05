<?php 
namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:255',
            'notifier_id' => 'required|integer|exists:users,id',
            'recipient_id' => 'required|integer|exists:users,id',
        ]);

        $notification = Notification::create([
            'message' => $request->message,
            'notifier_id' => $request->notifier_id,
            'recipient_id' => $request->recipient_id,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'error' => null,
        ], 201);
    }

    public function markAsRead($notifId)
    { 
        $notification = Notification::find($notifId);
        if ($notification) {
            $notification->is_read = true;
            $notification->save();
            return response()->json($notification, 200);
        }


        response()->json([
            'success' => true,
            'data' => null,
            'error' => 'Notification not found',
        ], 404);
    }

    public function getUserNotifications($companyId, $employeeId) {
      

        try {
            $notifications = Notification::where('recipient_id', $employeeId)->orderBy('created_at','desc')->get();

                
            return response()->json([
                'success' => true,
                'data' => $notifications,
                'error' => null,
                'ind' => array($employeeId,  $companyId)
            ], 201);

        } catch (\Throwable $th) {
            response()->json([
                'success' => true,
                'data' => null,
                'error' => $th->getMessage(),
            ], 404);
        }

    }


    public function getManagerNotifications($companyId, $employeeId) {
    
        try {
            $notifications = Notification::whereNot('notifier_id', $employeeId)->where('company_id', $companyId)->orderBy('created_at','desc');
            $getn = $notifications->get();
             
            return response()->json([
                'success' => true,
                'data' => $getn,
                'error' => null,
                'sserror' => $notifications->toSql() ,
                'ind' => array($employeeId,  $companyId)
            ], 201);
            
        } catch (\Throwable $th) {
            response()->json([
                'success' => true,
                'data' => null,
                'error' => $th->getMessage(),
            ], 404);
        }
    }
    public function getLatestNotifications(Request $request) {
        $userid = $request->user_id;
        $companyid = $request->company_id;
        $notifications = Notification::where('recipient_id', $userid)->where('company', $companyid)->latest(10);
    }
}
