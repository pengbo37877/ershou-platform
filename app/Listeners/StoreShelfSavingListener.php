<?php

namespace App\Listeners;

use App\Coupon;
use App\Events\CouponSaving;
use App\Events\StoreShelfSaving;
use App\StoreShelf;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreShelfSavingListener
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
     * @param  StoreShelfSaving  $event
     * @return void
     */
    public function handle(StoreShelfSaving $event)
    {
        $storeShelf = $event->storeShelf;
        if (empty($storeShelf->code)){
            $storeShelf->code = StoreShelf::findAvailableCode();
        }
    }
}
