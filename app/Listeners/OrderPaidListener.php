<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Order;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class OrderPaidListener
{
    private $app;

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
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {
        $order = $event->order;
        $user = $order->user;
        $books = $order->books;
        if (count($books) > 0) {
            $nameDesc = $books->first()->name;
        } else {
            $nameDesc = "";
        }
        $get = Cache::get('sale_order_' . $order->no . '_pay_success_notify');
        if (!$get && $order->type == Order::ORDER_TYPE_SALE) {
            Order::where('order_id',$order->id)->update([
                'paid_at' => Carbon::now(),
                'payment_method' => Order::PAYMENT_WECHAT,
                'sale_status' => Order::SALE_STATUS_PAID
            ]);
            Cache::put('sale_order_' . $order->no . '_pay_success_notify', 1, 60);
            try {
                if (env('SEND_WECHAT_MSG')) {
                    $this->app->template_message->send([
                        'touser' => $user->mp_open_id,
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                        'data' => [
                            'first' => '你买的《' . $nameDesc . '》等 ' . count($books) . ' 本书，已支付',
                            'keyword1' => $order->no,
                            'keyword2' => '已支付',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                    $this->app->template_message->send([
                        'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                        'data' => [
                            'first' => $order->user->nickname . '买了《' . $nameDesc . '》等 ' . count($books) . ' 本书， 已支付',
                            'keyword1' => $order->no,
                            'keyword2' => '已支付',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                }
            } catch (InvalidArgumentException $e) { }
        }
    }
}
