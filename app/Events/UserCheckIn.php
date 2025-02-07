<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCheckIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $user;
    public $companyId;

    public function __construct(User $user, $companyId)
    {
        $this->user = $user;
        $this->companyId = $companyId;
    }

    public function broadcastOn()
    {
        // Should match manager's listening channel
        return new Channel("company.{$this->companyId}.managers");
    }

    public function broadcastAs()
    {
        // Should match manager's listening channel
        return "company.{$this->companyId}.managers";
    }
    public function broadcastWith()
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->firstname
            ],
            'message' => "{$this->user->firstname} has checked in"
        ];
    }
}