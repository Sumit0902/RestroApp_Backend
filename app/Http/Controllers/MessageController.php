<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function conversations()
    {
        try {
            $user = Auth::user();
            $conversations = User::where('company_id', $user->company_id)
                ->where('id', '!=', $user->id)
                ->get()
                ->map(function ($contact) use ($user) {
                    $lastMessage = Message::where(function ($query) use ($user, $contact) {
                        $query->where('sender_id', $user->id)->where('receiver_id', $contact->id);
                    })->orWhere(function ($query) use ($user, $contact) {
                        $query->where('sender_id', $contact->id)->where('receiver_id', $user->id);
                    })->latest()->first();
    
                    return [
                        'id' => $contact->id,
                        'name' => $contact->firstname.' '.$contact->lastname,
                        'last_message' => $lastMessage ? $lastMessage->content : null,
                        'last_message_at' => $lastMessage ? $lastMessage->created_at->toISOString() : null,
                    ];
                });
     
            return response()->json([   
                'success' => false,
                'data' => $conversations,
                'error' => null, 
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([   
                'success' => false,
                'data' => null,
                'error' => $th->getMessage(), 
            ], 400);
        }
      
    }

    public function index(User $user)
    {
        $messages = Message::where(function ($query) use ($user) {
            $query->where('sender_id', Auth::id())->where('receiver_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('sender_id', $user->id)->where('receiver_id', Auth::id());
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'company_id' => Auth::user()->company_id,
            'content' => $request->content,
        ]);
        // event(new MessageSent($message))->broadcastOn(new PrivateChannel('chat.' . $message->chat_id));
        return response()->json($message);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array', // Validate that message_ids is an array
            'message_ids.*' => 'integer|exists:messages,id', // Validate each ID in the array
        ]);

        try {
            // Fetch messages that belong to the authenticated user and are in the provided IDs
            $messages = Message::whereIn('id', $request->message_ids)
                ->where('receiver_id', Auth::id()) // Ensure the authenticated user is the receiver
                ->get();

            if ($messages->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No messages found to mark as read.',
                ], 404); // Not Found
            }

            // Mark all messages as read
            $messages->each(function ($message) {
                $message->update(['is_read' => true]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read.',
                'data' => $messages,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read.',
                'error' => $th->getMessage(),
            ], 400);
        }
    }

    public function getUnreadMessages(Request $request)
    {
        try {
            // Fetch unread messages where the authenticated user is the receiver
            $unreadMessages = Message::where('receiver_id', Auth::id())
                ->where('is_read', false) // Only fetch unread messages
                ->orderBy('created_at', 'asc') // Order by creation time
                ->get();

            return response()->json([
                'success' => true,
                'data' => $unreadMessages,
                'error' => null,
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