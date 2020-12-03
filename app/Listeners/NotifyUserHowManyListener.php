<?php

namespace App\Listeners;

use App\BookSku;
use App\Events\NotifyUserHowMany;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class NotifyUserHowManyListener
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
     * @param  NotifyUserHowMany  $event
     * @return void
     */
    public function handle(NotifyUserHowMany $event)
    {
        $user = $event->user;
        $get = Cache::get('notify_user_'.$user->id.'_how_many');
        $how_many = Cache::remember('how_many', 60, function() {
            return BookSku::where('created_at', '>', now()->subDays(7))->count();
        });
        if (env('SEND_WECHAT_MSG') && !$get) {
            Cache::put('notify_user_'.$user->id.'_how_many', 1, 23*60);
            $this->app->template_message->send([
                'touser' => $user->mp_open_id,
                'template_id' => 'sHSe05upyZr0uK7q0hT_xXORWZ8dnGf4IxXqSCotBmQ',
                'url' => env('APP_URL') . '/wechat/shop',
                'data' => [
                    'first' => '回流鱼今日上新 '.$how_many.' 本，快去看看有没有你想要的！
                    ',
                    'keyword1' => '回流鱼上新',
                    'keyword2' => $how_many. '本',
                    'keyword3' => '低至1折起',
                    'keyword4' => '0',
                    'keyword5' => Carbon::now()->toDateTimeString(),
                    'remark' => '',
                ]
            ]);
        }
    }
}
