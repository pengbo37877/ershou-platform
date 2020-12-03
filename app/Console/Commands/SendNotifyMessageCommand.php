<?php

namespace App\Console\Commands;

use App\Events\SendNotifyMessage;
use Illuminate\Console\Command;

class SendNotifyMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notify message to activity users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $message = "高考是一个实现人生的省力杠杆，此时是你撬动它的最佳时机，并且以后你的人生会呈弧线上升。

💪<a href=env('APP_URL').'/wechat/shudan/1?from=notify'>加油！</a>💪";
        event(new SendNotifyMessage($message));
    }
}
