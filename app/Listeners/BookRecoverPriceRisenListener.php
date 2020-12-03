<?php

namespace App\Listeners;

use App\Events\BookRecoverPriceRisen;
use App\SaleItem;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookRecoverPriceRisenListener
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
     * @param  BookRecoverPriceRisen  $event
     * @return void
     */
    public function handle(BookRecoverPriceRisen $event)
    {
        // 当书籍状态改为收取的时候，会发送这个通知
        $book = $event->book;
        // 通知10人
        $saleItems = SaleItem::with("user")->where('book_id', $book->id)->take(10)->get();
        foreach ($saleItems as $saleItem) {
            $get = Cache::get('user_'.$saleItem->user_id.'_notify_book_can_recover');
            if ($book->admin_user_id>0 && $book->can_recover==1 && $saleItem->can_recover==0 && !$get) {
                $hour = now()->hour;
                // 一周个一个用户只发送一次
                if (env('SEND_WECHAT_MSG') && $hour>=8 && $hour<=21) {
                    Cache::put('user_'.$saleItem->user_id.'_notify_book_can_recover', 1, 7*24*60);
                    $this->app->template_message->send([
                        'touser' => $saleItem->user->mp_open_id,
                        'template_id' => 'sHSe05upyZr0uK7q0hT_xXORWZ8dnGf4IxXqSCotBmQ',
                        'url' => env('APP_URL').'/wechat/scan',
                        'data' => [
                            'first' => '回流鱼开始收取《' . $book->name . '》，快去清空你的书柜吧！
                    ',
                            'remark' => '旧书循环，有你一份功劳。'
                        ]
                    ]);
                }
                $saleItem->can_recover = 1;
                $saleItem->save();
            }
        }
    }
}
