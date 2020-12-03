<?php

namespace App\Listeners;

use App\Events\LotteryOpen;
use App\Lottery;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LotteryOpenListener
{
    protected $app, $xcx;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Application $app, \EasyWeChat\MiniProgram\Application $xcx)
    {
        $this->app = $app;
        $this->xcx = $xcx;
    }

    /**
     * Handle the event.
     *
     * @param  LotteryOpen  $event
     * @return void
     */
    public function handle(LotteryOpen $event)
    {
        $lotteryUser = $event->lotteryUser;
        $lottery = Lottery::find($lotteryUser->lottery_id);
        $user = $lotteryUser->user;
        if (env('SEND_WECHAT_MSG') && $user->mp_open_id) {
            $this->app->template_message->send([
                'touser' => $user->mp_open_id,
                'template_id' => '7aPhTfrzVHGcztvBM2CyjZle_mTrCMYmRDVueo7gIKI',
                "miniprogram" => [
                    "appid" => "wxdaefe97c067ccc5d",
                    "pagepath" => "pages/result/result?id=".$lotteryUser->lottery_id
                ],
                'data' => [
                    'first' => '抽奖结果通知',
                    'keyword1' => $lottery->title,
                    'keyword2' => $lottery->title,
                    'keyword3' => Carbon::now()->toDateTimeString(),
                    'remark' => '回流鱼抽奖机 参与的抽奖正在开奖，点击查看中奖名单',
                ]
            ]);
        }
        if (env('SEND_WECHAT_MSG') && $lotteryUser->form_id) {
            $this->xcx->template_message->send([
                'touser' => 'op8uO4kVUG-kTJMgAA4oQCeAaFDA',
                'template_id' => 'RieZ5veYMn0BYH5fFx3HhpRxQt4gNt4ECtHppN-2Y3U',
                'page' => 'pages/result/result?id='.$lotteryUser->lottery_id,
                'form_id' => $lotteryUser->form_id,
                'data' => [
                    'keyword1' => $lottery->title,
                    'keyword2' => $lottery->title,
                    'keyword3' => '回流鱼抽奖机 参与的抽奖正在开奖，点击查看中奖名单'
                ],
            ]);
        }
    }
}
