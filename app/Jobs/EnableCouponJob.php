<?php

namespace App\Jobs;

use App\Coupon;
use App\Events\SendCouponEnableMsg;
use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnableCouponJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $order; // sale_order >=已出库；recover_order == 已完成

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $type = $this->order->type;
        $sale_status = $this->order->sale_status;
        $recover_status = $this->order->recover_status;
        $closed = $this->order->closed;
        $user = $this->order->user;

        // 卖书订单
        if ($type == Order::ORDER_TYPE_SALE && $sale_status >= Order::SALE_STATUS_STOCK_OUT && $closed == 0) {
            // 激活邀请来源用户的券
            if ($user->qr_scene && intval($user->qr_scene)>0) {
                $coupon = Coupon::where('user_id', $user->qr_scene)->where('from_user', $user->id)->where('enabled', 0)->first();
                if ($coupon) {
                    $coupon->enabled = 1;
                    $coupon->save();
                    event(new SendCouponEnableMsg($coupon));
                }
            }
        }
        // 收书订单
        if ($type == Order::ORDER_TYPE_RECOVER && $recover_status == Order::RECOVER_STATUS_COMPLETE && $closed == 0) {
            // 激活邀请来源用户的券
            if ($user->qr_scene && intval($user->qr_scene)>0) {
                $coupon = Coupon::where('user_id', $user->qr_scene)->where('from_user', $user->id)->where('enabled', 0)->first();
                if ($coupon) {
                    $coupon->enabled = 1;
                    $coupon->save();
                    event(new SendCouponEnableMsg($coupon));
                }
            }
        }
    }
}
