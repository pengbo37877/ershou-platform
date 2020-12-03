<?php

namespace App\Events;

use App\RecoverReport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RecoverReportAccept
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $recoverReport;

    /**
     * Create a new event instance.
     *
     * @param RecoverReport $recoverReport
     */
    public function __construct(RecoverReport $recoverReport)
    {
        $this->recoverReport = $recoverReport;
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
