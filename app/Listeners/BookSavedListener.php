<?php

namespace App\Listeners;

use App\Events\BookSaved;
use App\SaleItem;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookSavedListener
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
     * @param  BookSaved  $event
     * @return void
     */
    public function handle(BookSaved $event)
    {
        // 发送通知给用户
        $book = $event->book;
        if ($book->admin_user_id > 0 && $book->can_recover == 1) {
            DB::update('update sale_items set can_recover=1 where book_id=?', [$book->id]);
        }
        // $saleItems = SaleItem::with("user")->where('book_id', $book->id)->get();
        // foreach ($saleItems as $saleItem) {
        //     $get = Cache::get('user_'.$saleItem->user_id.'_notify_book_can_recover');
        //     if ($book->admin_user_id>0 && $book->can_recover==1 && $saleItem->can_recover==0 && !$get) {
        //         $hour = now()->hour;
        //         // 一周个一个用户只发送一次
        //         if (env('SEND_WECHAT_MSG') && $hour>=8 && $hour<=21) {
        //             Cache::put('user_'.$saleItem->user_id.'_notify_book_can_recover', 1, 7*24*60);
        //             $this->app->template_message->send([
        //                 'touser' => $saleItem->user->mp_open_id,
        //                 'template_id' => 'rgch7KVIzLxC7yX0SwH9_HWK6e4VPufqKbbDBGsLww0',
        //                 'url' => env('APP_URL').'/wechat/scan',
        //                 'data' => [
        //                     'first' => '回流鱼开始收取《' . $book->name . '》，快去清空你的书柜吧！
        //             ',
        //                     'keyword1' => '《' . $book->name . '》',
        //                     'keyword2' => '1',
        //                     'keyword3' => '预收价 ' . $book->price * $book->discount / 100 . '元',
        //                     'keyword4' => now()->toDateTimeString(),
        //                     'remark' => '旧书循环，有你一份功劳。'
        //                 ]
        //             ]);
        //         }
        //         $saleItem->can_recover = 1;
        //         $saleItem->save();
        //     }
        // }
    }
}
