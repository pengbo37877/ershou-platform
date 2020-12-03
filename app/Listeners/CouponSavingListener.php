<?php

namespace App\Listeners;

use App\Coupon;
use App\Events\CouponSaving;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CouponSavingListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CouponSaving  $event
     * @return void
     */
    public function handle(CouponSaving $event)
    {
        $coupon = $event->coupon;
        if (empty($coupon->code)){
            $coupon->code = Coupon::findAvailableCode();
        }
    }
}
