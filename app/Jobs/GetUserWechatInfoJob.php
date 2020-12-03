<?php

namespace App\Jobs;

use App\Events\GetUserWechatInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GetUserWechatInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $openId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($openId)
    {
        $this->openId = $openId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        event(new GetUserWechatInfo($this->openId));
    }
}
