<?php

namespace App\Listeners;

use App\Events\OrderItemSaved;
use App\OrderItem;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderItemSavedListener
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
     * @param  OrderItemSaved  $event
     * @return void
     */
    public function handle(OrderItemSaved $event)
    {
        $orderItem = $event->orderItem;
        if (!empty($orderItem->review_result)) {
            DB::update('update books set can_recover=? where id=?', [$orderItem->review_result, $orderItem->book_id]);
            DB::update('update sale_items set can_recover=? where book_id=?', [$orderItem->review_result, $orderItem->book_id]);
        }
        Log::info('orderItem '.$orderItem->id.' review_result='.$orderItem->review_result);
    }
}
