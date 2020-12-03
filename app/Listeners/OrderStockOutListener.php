<?php

namespace App\Listeners;

use App\Coupon;
use App\Events\OrderStockOut;
use App\Events\SendCouponEnableMsg;
use App\Order;
use App\ReminderItem;
use App\User;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderStockOutListener
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
     * @param  OrderStockOut  $event
     * @return void
     */
    public function handle(OrderStockOut $event)
    {
        $order = $event->order;
        $user = $order->user;

        if ($order->type == Order::ORDER_TYPE_SALE
            && $order->sale_status == Order::SALE_STATUS_STOCK_OUT && $order->closed==0) {

            $orderItems = $order->items;
            $orderItems->each(function($item) use ($order){
                ReminderItem::where('user_id', $order->user_id)
                    ->where('book_id', $item->book_id)
                    ->delete();
            });

            // 激活邀请来源用户的券
            $coupon = Coupon::where('user_id', $user->qr_scene)
                ->where('from_user', $user->id)
                ->where('enabled', 0)
                ->first();
            if ($coupon) {
                $coupon->enabled = 1;
                $coupon->not_before = Carbon::now();
                $coupon->not_after = Carbon::now()->addDays(30);    // 20 元满减券，有效期 30 天
                $coupon->save();
                event(new SendCouponEnableMsg($coupon));
            }
        }
    }
}
