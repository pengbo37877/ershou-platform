<?php

namespace App\Events;

use App\Book;
use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BookOnSale
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $user_id;
    public $book_id;

    /**
     * Create a new event instance.
     *
     * @param $user_id
     * @param $book_id
     */
    public function __construct($user_id, $book_id)
    {
        $this->user_id = $user_id;
        $this->book_id = $book_id;
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
