<?php

namespace App\Listeners;

use App\Coupon;
use App\Events\GetUserWechatInfo;
use App\Jobs\GiveCouponsJob;
use App\User;
use Barryvdh\Snappy\Facades\SnappyImage;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GetUserWechatInfoListener
{
    public $app;

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
     * @param  GetUserWechatInfo  $event
     * @return void
     */
    public function handle(GetUserWechatInfo $event)
    {
        $fu = $this->app->user->get($event->openId);

        Log::info('GetUserWechatInfo ' . json_encode($fu, JSON_UNESCAPED_UNICODE));

        $unionId = isset($fu['unionid']) ? $fu['unionid'] : '';
        if (is_null($unionId) || empty($unionId)) {
            return;
        }
        $user = User::where('union_id', $unionId)->first();
        if ($user) {
            // 更新用户关注状态
            $subscribe = isset($fu['subscribe']) ? $fu['subscribe'] : 0;
            $user->subscribe = isset($fu['subscribe']) ? $fu['subscribe'] : 0;
            if ($subscribe != 0) {
                $user->mp_open_id = $event->openId;
                $user->subscribe_scene = isset($fu['subscribe_scene']) ? $fu['subscribe_scene'] : '';
                $user->subscribe_time = isset($fu['subscribe_time']) ? $fu['subscribe_time'] : '';
                $user->union_id = isset($fu['unionid']) ? $fu['unionid'] : '';
                $user->province = isset($fu['province']) ? $fu['province'] : '';
                $user->city = isset($fu['city']) ? $fu['city'] : '';
            }
            $user->save();
        } else {
            Log::info('GetUserWechatInfoListener create new user');
            $user = new User();
            $user->mp_open_id = $event->openId;
            $user->nickname = isset($fu['nickname']) ? $fu['nickname'] : '';
            $user->sex = isset($fu['sex']) ? $fu['sex'] : '';
            $user->avatar = isset($fu['headimgurl']) ? $fu['headimgurl'] : '';
            $user->subscribe = isset($fu['subscribe']) ? $fu['subscribe'] : '';
            $user->subscribe_scene = isset($fu['subscribe_scene']) ? $fu['subscribe_scene'] : '';
            $user->subscribe_time = isset($fu['subscribe_time']) ? $fu['subscribe_time'] : '';
            $user->union_id = isset($fu['unionid']) ? $fu['unionid'] : '';
            $user->province = isset($fu['province']) ? $fu['province'] : '';
            $user->city = isset($fu['city']) ? $fu['city'] : '';
            $user->qr_scene = isset($fu['qr_scene']) ? $fu['qr_scene'] : '';
            $user->qr_scene_str = isset($fu['qr_scene_str']) ? $fu['qr_scene_str'] : '';
            $user->save();
            Log::info('GetUserWechatInfoListener new user=' . $user->id . ' qr_scene=' . $user->qr_scene);
            GiveCouponsJob::dispatch($user);
        }
        // file_get_contents('https://huiliuyu.com/wx-api/send_share_image/' . $user->id);
    }
}
