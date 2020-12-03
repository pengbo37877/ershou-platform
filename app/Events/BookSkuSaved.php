<?php

namespace App\Events;

use App\BookSku;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BookSkuSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bookSku;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(BookSku $bookSku)
    {
        $this->bookSku = $bookSku;
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
