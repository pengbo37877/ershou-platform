<?php

namespace App\Console\Commands;

use App\Jobs\SendLotteryNotificationJob;
use App\Lottery;
use Illuminate\Console\Command;

class SendLotteryNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lottery:notify {lottery}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send lottery notification to users';

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
        $lottery_id = $this->argument('lottery');
        $lottery = Lottery::find($lottery_id);
        dispatch(new SendLotteryNotificationJob($lottery));
    }
}
