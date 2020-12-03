<?php

namespace App\Listeners;

use App\Events\ReminderCreated;
use App\ReminderItem;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class ReminderCreatedListener
{
    protected $app;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the event.
     *
     * @param  ReminderCreated  $event
     * @return void
     */
    public function handle(ReminderCreated $event)
    {
        $reminder = $event->reminder;
        $count = ReminderItem::where('book_id', $reminder->book_id)->count();
        DB::update('update books set reminder_count=? where id=?', [$count, $reminder->book_id]);
        if (env('SEND_WECHAT_MSG')) {
            $this->app->template_message->send([
                'touser' => $reminder->user->mp_open_id,
                'template_id' => 'rgch7KVIzLxC7yX0SwH9_HWK6e4VPufqKbbDBGsLww0',
                'url' => env('APP_URL') . '/wechat/book/' . $reminder->book->isbn,
                'data' => [
                    'first' => '你已订阅了《' . $reminder->book->name . '》，一有用户提供给回流鱼，你就会得到通知',
                    'keyword1' => '《' . $reminder->book->name . '》',
                    'keyword2' => '1',
                    'keyword3' => '定价为' . $reminder->book->price . ' 预计购买折扣为' . intval($reminder->book->sale_discount) / 10 . '折',
                    'keyword4' => $reminder->created_at->toDateTimeString(),
                    'remark' => '阅读不孤读'
                ]
            ]);
        }
    }
}
