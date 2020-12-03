<?php

namespace App\Events;

use App\BookVersion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BookVersionSaving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bookVersion;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(BookVersion $bookVersion)
    {
        $this->bookVersion = $bookVersion;
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
