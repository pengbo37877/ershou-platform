<?php

namespace App\Jobs;

use App\BookSku;
use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FackSoldSkuResaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 把实际没有卖出的SKU重新上架
        $items = BookSku::whereHas('to_order', function($q) {
            $q->where('sale_status', Order::SALE_STATUS_CANCEL);
        })->where('status', BookSku::STATUS_SOLD)->get();
        foreach ($items as $item) {
            $item->update([
                'sale_status' => BookSku::STATUS_FOR_SALE
            ]);
        }
        $items = BookSku::whereHas('to_order', function($q) {
            $q->where('closed', 1);
        })->where('status', BookSku::STATUS_SOLD)->get();
        foreach ($items as $item) {
            $item->update([
                'sale_status' => BookSku::STATUS_FOR_SALE
            ]);
        }
    }
}
