<?php

namespace App\Listeners;

use App\Book;
use App\BookSku;
use App\CartItem;
use App\Events\BookOnSale;
use App\ReminderItem;
use App\User;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class BookOnSaleListener
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
     * @param  BookOnSale  $event
     * @return void
     */
    public function handle(BookOnSale $event)
    {
        $user = User::find($event->user_id);
        $book = Book::find($event->book_id);
        // 在售图书
        $sku = BookSku::where('book_id', $event->book_id)
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->first();
        $reminder = ReminderItem::where('user_id', $event->user_id)
            ->where('book_id', $event->book_id)
            ->first();
        $cart = CartItem::where('user_id', $event->user_id)
            ->where('book_id', $event->book_id)
            ->first();
        // 一个用户8小时只发送一条通知
        $getReminder = Cache::get('reminder_' . $user->mp_open_id . '_' . $event->book_id);
        if ($sku && $reminder && !$cart && !$getReminder && now()->hour > 9 && now()->hour < 21) {
            // 一天以内可以取消
            Cache::put('reminder_' . $user->mp_open_id . '_' . $event->book_id, 1, 8 * 60);
            // 更新reminder的notify_times
            $reminder->notify_times = $reminder->notify_times + 1;
            $reminder->save();
            if (env('SEND_WECHAT_MSG')) {
                $this->app->template_message->send([
                    'touser' => $user->mp_open_id,
                    'template_id' => '2JftEoBGA-3lk__j58-nIldqd38rispqmF9E_b5d9sQ',
                    'url' => env('APP_URL') . '/wechat/cart?isbn=' . $book->isbn,
                    'data' => [
                        'first' => '《' . $book->name . '》已经有用户提供给了回流鱼，快去抢购吧
                    ',
                        'keyword1' => $book->name,
                        'keyword2' => '1',
                        'keyword3' => $sku->price . '元（' . $sku->title . '）',
                        'keyword4' => '0',
                        'keyword5' => Carbon::now()->toDateTimeString(),
                        'remark' => '如果不想接收到本书的消息，可以在24小时内在公号内回复 ' . $event->book_id,
                    ]
                ]);
            }
        }

        // 更新图书 最低折扣和 折扣价格
        $book_id = $event->book_id;
        $bookSkus = BookSku::where('book_id', $book_id)
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->get();
        $min_price = 0;
        if (count($bookSkus) > 0) {
            $min_price = $bookSkus->min('price');
        }
        $book = Book::find($book_id);
        $price = intval($book->price);
        if ($min_price>0 && $price>0) {
            $sale_discount = ceil($min_price * 100 / $book->price);
            $book->sale_discount        = $sale_discount;
            $book->sale_discount_price  = $min_price;
            $book->save();
        }
    }
}
