<?php

namespace App\Events;

use App\Picture;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PictureSaving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $picture;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Picture $picture)
    {
        $this->picture = $picture;
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
