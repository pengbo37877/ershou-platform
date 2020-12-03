<?php

namespace App\Events;

use App\Shudan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ShudanSaving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shudan;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Shudan $shudan)
    {
        $this->shudan = $shudan;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
