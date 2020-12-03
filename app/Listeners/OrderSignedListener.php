<?php

namespace App\Listeners;

use App\Events\OrderSigned;
use App\Order;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderSignedListener
{
    protected  $app;

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
     * @param  OrderSigned  $event
     * @return void
     */
    public function handle(OrderSigned $event)
    {
        $order = $event->order;
        if ($order->type == Order::ORDER_TYPE_RECOVER) {
            try {
                if (env('SEND_WECHAT_MSG')) {
                    $this->app->template_message->send([
                        'touser' => $order->user->mp_open_id,
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/recover_order/' . $order->no,
                        'data' => [
                            'first' => '回流鱼已签收',
                            'keyword1' => $order->no,
                            'keyword2' => '已签收，请等待回流鱼人工审核结果',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                    // 给魏总发一条
                    $this->app->template_message->send([
                        'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/recover_order/' . $order->no,
                        'data' => [
                            'first' => '回流鱼已签收',
                            'keyword1' => $order->no,
                            'keyword2' => '已签收，请等待回流鱼人工审核结果',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                }
            } catch (InvalidArgumentException $e) {
            }
        }
    }
}
