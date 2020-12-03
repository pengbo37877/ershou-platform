<?php

namespace App\Listeners;

use App\Book;
use App\BookSku;
use App\CartItem;
use App\Events\OrderClosed;
use App\Order;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderClosedListener
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
     * @param  OrderClosed  $event
     * @return void
     */
    public function handle(OrderClosed $event)
    {
        $order = $event->order;
        if($order->order_id){return;}
        if ($order->type == Order::ORDER_TYPE_SALE) {
            Order::where('order_id',$order->id)->update(['closed'=>1]);
            try {
                if (env('SEND_WECHAT_MSG')) {
                    $this->app->template_message->send([
                        'touser' => $order->user->mp_open_id,
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                        'data' => [
                            'first' => '订单未支付，已关闭',
                            'keyword1' => $order->no,
                            'keyword2' => '已关闭',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);

                    // 给魏总发一条
                    $this->app->template_message->send([
                        'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                        'data' => [
                            'first' => '订单未支付，已关闭',
                            'keyword1' => $order->no,
                            'keyword2' => '已关闭',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                }
                $items = $order->allitems;
                $items->each(function ($item) {
                    // 给购物车中的用户发送通知
                    $carItems = CartItem::where('book_id', $item->book_id)->get();
                    $carItems->each(function($ci) {
                        $sku = BookSku::where('book_id', $ci->book_id)
                            ->where('status', BookSku::STATUS_FOR_SALE)
                            ->first();

                        if (env('SEND_WECHAT_MSG') && $sku) {
                            $this->app->template_message->send([
                                'touser' => $ci->user->mp_open_id,
                                'template_id' => 'rgch7KVIzLxC7yX0SwH9_HWK6e4VPufqKbbDBGsLww0',
                                'url' => env('APP_URL').'/wechat/cart?isbn=' . $ci->book->isbn,
                                'data' => [
                                    'first' => '《' . $ci->book->name . '》购买用户取消了，快去抢购吧！',
                                    'keyword1' => '《' . $ci->book->name . '》',
                                    'keyword2' => '1',
                                    'keyword3' => $sku->price . '元（' . $sku->title . '）',
                                    'keyword4' => Carbon::now()->toDateTimeString(),
                                    'remark' => '如果不想接收到本书的消息，可以在24小时内在公号内回复 ' . $ci->book_id,
                                ]
                            ]);
                        }
                    });
                });
            } catch (InvalidArgumentException $e) {
            }
        }
    }
}
