<?php

namespace App\Events;

use App\LotteryUser;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LotteryOpen
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lotteryUser;

    /**
     * Create a new event instance.
     *
     * @param LotteryUser $lotteryUser
     */
    public function __construct(LotteryUser $lotteryUser)
    {
        $this->lotteryUser = $lotteryUser;
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
