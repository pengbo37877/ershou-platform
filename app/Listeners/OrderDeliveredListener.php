<?php

namespace App\Listeners;

use App\Coupon;
use App\Events\OrderDelivered;
use App\Order;
use App\User;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class OrderDeliveredListener
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
     * @param  OrderDelivered  $event
     * @return void
     */
    public function handle(OrderDelivered $event)
    {
        $order  = $event->order;
        $user   = $event->order->user;
        $items  = $order->items;
        $book   = $items->first()->book;
        $get = Cache::get('order_delivered_'.$order->id);
        if (!$get) {
            Cache::put('order_delivered_'.$order->id, true, 24*60*10);
            if ($order->type == Order::ORDER_TYPE_SALE) {
                // 过期订单不发消息
                $validDatetime = now()->subDays(10)->toDateTimeString();
                if (env('SEND_WECHAT_MSG') && $order->created_at > $validDatetime) {
                    $this->app->template_message->send([
                        'touser' => $order->user->mp_open_id,
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                        'data' => [
                            'first' => '你在回流鱼买的《' . $book->name . '》等 ' . count($items) . ' 本书已经发货，请注意查收',
                            'keyword1' => $order->no,
                            'keyword2' => '已发货',
                            //'keyword3' => Carbon::now()->toDateTimeString()
                            'keyword3' => $order->created_at
                        ]
                    ]);
                }
                // 激活A用户的券
                if (is_numeric($user->qr_scan_str)) {
                    $coupon = Coupon::where('user_id', $user->qr_scan_str)->where('from_user', $user->id)->where('enabled', 0)->first();
                    if ($coupon) {
                        $coupon->enabled = 1;
                        $coupon->save();
                        $userA = User::find($user->qr_scan_str);
                        if (env('SEND_WECHAT_MSG')) {
                            $this->app->template_message->send([
                                'touser' => $userA->mp_open_id,
                                'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                                'url' => env('APP_URL') . '/wechat/shop',
                                'data' => [
                                    'first' => '你的买书现金券已激活
                                快去回流鱼买书吧
                                ',
                                    'keyword1' => $user->nickname . ' 买了书，你得了券',
                                    'keyword2' => '是不是很开心，O(∩_∩)O哈哈哈~',
                                    'keyword3' => Carbon::now()->toDateTimeString()
                                ]
                            ]);
                        }
                    }
                }
            } else {
                $user = $order->user;
                if (env('SEND_WECHAT_MSG')) {
                    $this->app->template_message->send([
                        'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/recover_order/' . $order->no,
                        'data' => [
                            'first' => $user->nickname . ' [' . $user->id . '] 卖给回流鱼的《' . $book->name . '》等' . count($items) . '本书已经发货，
请注意查收！
                        ',
                            'keyword1' => $order->no,
                            'keyword2' => '已发货',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                }
            }
        }
    }
}
