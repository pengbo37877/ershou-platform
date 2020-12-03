<?php

namespace App\Events;

use App\StoreShelf;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StoreShelfSaving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $storeShelf;

    /**
     * Create a new event instance.
     *
     * @param StoreShelf $storeShelf
     */
    public function __construct(StoreShelf $storeShelf)
    {
        $this->storeShelf = $storeShelf;
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
