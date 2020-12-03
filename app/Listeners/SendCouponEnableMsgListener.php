<?php

namespace App\Listeners;

use App\Events\SendCouponEnableMsg;
use App\User;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCouponEnableMsgListener
{
    protected $app;

    /**
     * Create the event listener.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the event.
     *
     * @param  SendCouponEnableMsg  $event
     * @return void
     */
    public function handle(SendCouponEnableMsg $event)
    {
        $coupon = $event->coupon;
        $user = User::find($coupon->user_id);
        $from_user = User::find($coupon->from_user);
        if ($coupon->enabled == 0) {
            $coupon->enabled = 1;
            $coupon->save();
        }
        if (env('SEND_WECHAT_MSG') && $user && $from_user && $coupon->enabled) {
            $this->app->template_message->send([
                'touser' => $user->mp_open_id,
                'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                'url' => env('APP_URL') . '/wechat/shop',
                'data' => [
                    'first' => '你的' . $coupon->name . '已激活
                            ',
                    'keyword1' => '邀请更多好友参与书籍循环吧',
                    'keyword2' => '是不是很开心，O(∩_∩)O哈哈哈~',
                    'keyword3' => now()->toDateTimeString()
                ]
            ]);
        }
    }
}
