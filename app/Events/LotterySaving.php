<?php

namespace App\Events;

use App\Lottery;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LotterySaving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lottery;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Lottery $lottery)
    {
        $this->lottery = $lottery;
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
