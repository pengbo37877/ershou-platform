<?php

namespace App\Jobs;

use App\Events\LotteryOpen;
use App\Lottery;
use App\LotteryUser;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendLotteryNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $lotteryUser;

    /**
     * Create a new job instance.
     *
     * @param LotteryUser $lotteryUser
     */
    public function __construct(LotteryUser $lotteryUser)
    {
        $this->lotteryUser = $lotteryUser;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        event(new LotteryOpen($this->lotteryUser));
    }
}
