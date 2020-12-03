<?php

namespace App\Jobs;

use App\BookSku;
use App\Order;
use App\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class AutoXiajiaSku implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $skus;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        Log::info('自动下架');
        $this->skus = BookSku::where('status', BookSku::STATUS_FOR_SALE)->with(['orders', function($q){
            $q->where('type', Order::ORDER_TYPE_SALE);
            $q->where('sale_status', '<>', Order::SALE_STATUS_CANCEL);
            $q->where('closed', false);
            $q->where('paid_at', '<>', null);
        }])->get();
        Log::info('skus count='.count($this->skus));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->skus->each(function ($sku){
            if (count($sku->orders) > 0) {
                $sku->status = BookSku::STATUS_SOLD;
                $sku->save();
            }
        });
    }
}
