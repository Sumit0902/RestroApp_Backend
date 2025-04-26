<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('private-chat.{receiverId}', function ($user, $receiverId) {
    \Log::info('Channel auth attempt', ['user_id' => $user->id, 'receiver_id' => $receiverId]);

    // Authorize if sender and receiver are in the same company
    $receiver = \App\Models\User::find($receiverId);
    return $receiver && $user->company_id === $receiver->company_id;
});
// Broadcast::channel('chat.{id}', function ($user, $id) {
//     return true; // Replace with appropriate authorization logic
// });