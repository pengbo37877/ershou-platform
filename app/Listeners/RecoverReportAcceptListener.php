<?php

namespace App\Listeners;

use App\Events\RecoverReportAccept;
use App\RecoverReport;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RecoverReportAcceptListener
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
     * @param  RecoverReportAccept  $event
     * @return void
     */
    public function handle(RecoverReportAccept $event)
    {
        $book = $event->recoverReport->book;
        $user = $event->recoverReport->user;
        if ($book) {
            if (env('SEND_WECHAT_MSG')) {
                $get = Cache::get($user->id.'_accept_'.$book->id);
                if (!$get) {
                    Cache::put($user->id.'_accept_'.$book->id, 1, 60);
                    $this->app->template_message->send([
                        'touser' => $user->mp_open_id,
                        'template_id' => 'pwNPJiOHhVvaMtD63fBGW-lW7bBhEyo5kf9P6ikCzTY',
                        'url' => env('APP_URL') . '/wechat/scan',
                        'data' => [
                            'first' => '《' . $book->name . '》回流鱼开始收取了，快去看看收购价吧',
                            'keyword1' => $user->nickname . '认为《' . $book->name . '》' .
                                RecoverReport::$typeMap[$event->recoverReport->type] . '  ' . $event->recoverReport->reason,
                            'keyword2' => $event->recoverReport->created_at->toDateTimeString(),
                            'keyword3' => $event->recoverReport->book->can_recover ? '已接受' : '',
                            'remark' => '感谢你的反馈！回流鱼会越变越聪明的，嘿嘿😜'
                        ]
                    ]);
                    // 给魏总发送一条
                    $this->app->template_message->send([
                        'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                        'template_id' => 'pwNPJiOHhVvaMtD63fBGW-lW7bBhEyo5kf9P6ikCzTY',
                        'url' => env('APP_URL') . '/wechat/scan',
                        'data' => [
                            'first' => '《' . $book->name . '》回流鱼开始收取了，快去看看收购价吧',
                            'keyword1' => $user->nickname . '认为《' . $book->name . '》' .
                                RecoverReport::$typeMap[$event->recoverReport->type] . '  ' . $event->recoverReport->reason,
                            'keyword2' => $event->recoverReport->created_at->toDateTimeString(),
                            'keyword3' => $event->recoverReport->book->can_recover ? '已接受' : '',
                            'remark' => '感谢你的反馈！回流鱼会越变越聪明的，嘿嘿😜'
                        ]
                    ]);
                }
            }
        }
    }
}
