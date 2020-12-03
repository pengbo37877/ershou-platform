<?php

namespace App\Jobs;

use App\BookSku;
use App\CartItem;
use App\Order;
use App\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

class CancelOrderJob implements ShouldQueue
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
        $get = Cache::get('cancel_order_' . $this->order->no);
        if ($get) {
            return;
        }

        if ($this->order->type == Order::ORDER_TYPE_RECOVER) {
            return;
        }
        if($this->order->order_id){return;}

        Cache::put('cancel_order_' . $this->order->no, 1, 60);
        // 已经关闭的 已经取消的 和已经支付的不做处理
        if ($this->order->closed == Order::PAYING_STATUS_CLOSE
            || $this->order->paid_at
            || $this->order->sale_status == Order::SALE_STATUS_CANCEL){
            Order::where('order_id',$this->order->id)->update([
                'closed' => $this->order->closed,
                'sale_status' => $this->order->sale_status
            ]);
            return;
        }

        // 把sku的状态改为售卖
        $items = OrderItem::with('bookSku')
            ->where('order_id', $this->order->id)->orWhere('up_id',$this->order->id)
            ->get();
        $data = [];
        foreach ($items as $item) {
            $book_sku = $item->bookSku;
            if($book_sku->ifnew == 1){
                $book_sku->stock += $item->amount;
            }
            $book_sku->status = BookSku::STATUS_FOR_SALE;
            $book_sku->sold_at = null;
            $book_sku->to_order = 0;
            $book_sku->save();
            // 把购买失败的书再放到用户的购物袋中？
            array_push($data, [
                'user_id'       => $this->order->user_id,
                'book_id'       => $item->book_id,
                'book_sku_id'   => $item->book_sku_id,
                'amount'        => 1,
                'selected'      => 1
            ]);
        }
        CartItem::insert($data);

        // 如果使用了现金券，把现金券重新标记为未使用
        if ($this->order->coupon_id) {
            $coupon         = $this->order->coupon;
            $coupon->used   = 0;

            $coupon->save();
        }

        // 取消订单
        $order = $this->order;
        $order->sale_status = Order::SALE_STATUS_CANCEL;
        $order->save();
        Order::where('order_id',$order->id)->update(['sale_status' => Order::SALE_STATUS_CANCEL]);
    }
}
