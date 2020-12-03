<?php

namespace App\Listeners;

use App\BookSku;
use App\CartItem;
use App\Events\SkuForSale;
use App\ReminderItem;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SkuForSaleListener
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
     * @param  SkuForSale  $event
     * @return void
     */
    public function handle(SkuForSale $event)
    {
        $sku = $event->sku;
        $reminders = ReminderItem::where('book_id', $sku->book_id)->get();
        $reminders->each(function ($reminder) use ($sku){
            if (env('SEND_WECHAT_MSG')) {
                $this->app->template_message->send([
                    'touser' => $reminder->user->mp_open_id,
                    'template_id' => 'rgch7KVIzLxC7yX0SwH9_HWK6e4VPufqKbbDBGsLww0',
                    'url' => env('APP_URL') . '/wechat/book/' . $reminder->book->isbn . '?from=1',
                    'data' => [
                        'first' => '你订阅的《' . $reminder->book->name . '》已经有用户提供给了回流鱼，快去抢购吧',
                        'keyword1' => '《' . $reminder->book->name . '》',
                        'keyword2' => '1',
                        'keyword3' => $sku->price . '元（' . number_format($sku->price * 10 / $sku->original_price, 1) . '折）',
                        'keyword4' => $reminder->created_at->toDateTimeString(),
                        'remark' => '如果不想接收到本书的消息，请去购物袋中取消'
                    ]
                ]);
            }
        });
    }
}
