<?php

namespace App\Jobs;

use App\BookSku;
use App\BookVersion;
use App\Order;
use App\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateSkuFromOrderItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

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
        if ($this->order->type == Order::ORDER_TYPE_RECOVER && $this->order->recover_status == Order::RECOVER_STATUS_COMPLETE) {
            $sku_empty_items = OrderItem::with('book')->with('book_version')->where('order_id', $this->order->id)
                ->whereNull('book_sku_id')->where('review_result', 1)->get();
            foreach ($sku_empty_items as $item) {
                $data = [
                    'user_id' => $this->order->user_id,
                    'book_id' => $item->book_id,
                    'book_version_id' => $item->book_version_id?$item->book_version_id:0,
                    'isbn' => $item->book->isbn,
                    'recover_price' => $item->reviewed_price,
                    'original_price' => $this->buildOriginalPrice($item),
                    'level' => $item->level,
                    'title' => $item->title,
                    'status' => BookSku::STATUS_RETREADING,
                    'hly_code' => $item->hly_code,
                    'groups' => $item->groups,
                    'price' => $item->sale_price,
                    'from_order' => $this->order->id,
                    'description' => "",
                    'mark' => ""
                ];
                $sku = BookSku::create($data);
                if (!$sku) {
                    $sku = BookSku::create($data);
                }
            }
        }
    }

    function buildOriginalPrice($orderItem) {
        if ($orderItem->book_version){
            return $orderItem->book_version->price;
        }
        return $orderItem->book->price;
    }
}
