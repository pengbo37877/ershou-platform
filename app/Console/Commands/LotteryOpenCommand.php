<?php

namespace App\Console\Commands;

use App\Jobs\SendLotteryNotificationJob;
use App\Lottery;
use App\LotteryUser;
use Illuminate\Console\Command;

class LotteryOpenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lottery:open';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Open Lottery';

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
        $lotteries = Lottery::where('type', Lottery::TYPE_TIME)->where('status', Lottery::STATUS_RUNNING)->get();
        $lotteries->each(function($lottery) {
            if ($lottery->end_at<=now()) {
                $participants_count = LotteryUser::where('lottery_id', $lottery->id)->count();
                if ($participants_count<$lottery->winner_count) {
                    $lottery->status = Lottery::STATUS_END_WITH_NOTHING;
                    $lottery->save();
                    return;
                }
                // 参与的人数大于开奖人数
                $randomUsers = LotteryUser::where('lottery_id', $lottery->id)->get()
                    ->random($lottery->winner_count);
                $randomUsers->each(function ($u) {
                    $u->win = 1;
                    $u->save();
                });
                $lottery->notified=1;
                $lottery->status = Lottery::STATUS_END_WITH_RESULT;
                $lottery->save();
                $lotteryUsers = LotteryUser::with('user')->where('lottery_id', $lottery->id)->get();
                $lotteryUsers->each(function($lotteryUser){
                    SendLotteryNotificationJob::dispatchNow($lotteryUser);
                });
            }
        });
        $lotteries = Lottery::where('notified', 0)->where('status', 2)->get();
        $lotteries->each(function($lottery) {
            $participants_count = LotteryUser::where('lottery_id', $lottery->id)->count();
            if ($participants_count<$lottery->winner_count) {
                $lottery->status = Lottery::STATUS_END_WITH_NOTHING;
                $lottery->save();
            }else {
                $lottery->notified=1;
                $lottery->save();
                $lotteryUsers = LotteryUser::with('user')->where('lottery_id', $lottery->id)->get();
                $lotteryUsers->each(function($lotteryUser){
                    SendLotteryNotificationJob::dispatchNow($lotteryUser);
                });
            }
        });
    }
}
