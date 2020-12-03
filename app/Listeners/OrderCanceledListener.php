<?php

namespace App\Listeners;

use App\BookSku;
use App\CartItem;
use App\Events\OrderCanceled;
use App\Order;
use App\OrderItem;
use App\SaleItem;
use App\Wallet;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCanceledListener
{
    public $app;
    public $payment;

    /**
     * Create the event listener.
     *
     * @param Application $app
     * @param \EasyWeChat\Payment\Application $payment
     */
    public function __construct(Application $app, \EasyWeChat\Payment\Application $payment)
    {
        $this->app = $app;
        $this->payment = $payment;
    }

    /**
     * Handle the event.
     *
     * @param  OrderCanceled $event
     * @return void
     * @throws InvalidArgumentException
     */
    public function handle(OrderCanceled $event)
    {
        $order = $event->order;
        Log::info('订单取消'.$order->no);
        if ($order->type == Order::ORDER_TYPE_SALE) {
            Order::where('order_id',$order->id)->update(['sale_status' => Order::SALE_STATUS_CANCEL]);
            if ($order->paid_at) {
                if ($order->payment_method == Order::PAYMENT_WECHAT) {
                    // 微信支付支付成功的才退款
                    $sub_refund_amount = $order->refunds->sum->amount;
                    $refund_fee = ($order->total_amount-$sub_refund_amount)*100;
                    $total_fee = $order->total_amount*100;
                    $order->refund_no = Order::getAvailableRefundNo();
                    $order->save();
                    $result = $this->payment->refund->byOutTradeNumber(
                        $order->no,
                        $order->refund_no,
                        $total_fee,
                        $refund_fee,
                        [
                            // 可在此处传入其他参数，详细参数见微信支付文档
                            'refund_desc' => '订单退款',
                        ]);
                    Log::info('total_fee='.$total_fee);
                    Log::info('refund_fee='.$refund_fee);
                    Log::info($result);
                    if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                        $order->refund_status = Order::REFUND_STATUS_SUCCESS;
                        $order->save();
                        Order::where('order_id',$order->id)->update(['refund_status' => Order::REFUND_STATUS_SUCCESS]);
                    }
                }else if ($order->payment_method == Order::PAYMENT_WALLET) {
                    // 退钱到钱包
                    $wallet_item = Wallet::create([
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'type' => Wallet::TYPE_BUY_BOOK_REFUND,
                        'status' => Wallet::STATUS_SUCCESS,
                        'amount' => $order->total_amount
                    ]);
                    if ($wallet_item) {
                        $order->refund_status = Order::REFUND_STATUS_SUCCESS;
                        $order->save();
                        Order::where('order_id',$order->id)->update(['refund_status' => Order::REFUND_STATUS_SUCCESS]);
                    }
                }
            }

            $order->allitems->each(function ($item) use ($order){
                $book_sku = $item->bookSku;
                // 查看在售的是否有同品相的
                if($book_sku->ifnew == 1){
                    $book_sku->stock += $item->amount;
                }
                $book_sku->status = BookSku::STATUS_FOR_SALE;
                $book_sku->sold_at = null;
                $book_sku->to_order = 0;
                $book_sku->save();

                // 给购物车中的用户发送通知
                $carItems = CartItem::where('book_id', $item->book_id)->where('created_at', '>', now()->subHours(6))->get();
                $carItems->each(function($ci) use ($book_sku){
                    if (env('SEND_WECHAT_MSG')) {
                        $this->app->template_message->send([
                            'touser' => $ci->user->mp_open_id,
                            'template_id' => 'rgch7KVIzLxC7yX0SwH9_HWK6e4VPufqKbbDBGsLww0',
                            'url' => env('APP_URL') . '/wechat/cart?isbn=' . $ci->book->isbn,
                            'data' => [
                                'first' => '《' . $ci->book->name . '》购买用户取消了，快去抢购吧！
                    ',
                                'keyword1' => '《' . $ci->book->name . '》',
                                'keyword2' => '1',
                                'keyword3' => $book_sku->price . '元（' . $book_sku->title . '）',
                                'keyword4' => Carbon::now()->toDateTimeString(),
                                'remark' => '如果不想接收到本书的消息，可以在24小时内在公号内回复 ' . $ci->book_id,
                            ]
                        ]);
                    }
                });


                // 把购买失败的书再放到用户的购物袋中？
                CartItem::create([
                    'user_id' => $order->user_id,
                    'book_id' => $item->book_id,
                    'book_sku_id' => $item->book_sku_id,
                    'amount' => 1,
                    'selected' => 1
                ]);
            });

            // 如果使用了现金券，把现金券重新标记为未使用
            if ($order->coupon_id){
                $coupon = $order->coupon;
                $coupon->used=0;
                $coupon->save();
            }
        }else if($order->type == Order::ORDER_TYPE_RECOVER) {
            Log::info('用户取消了卖书给回流鱼的订单'.$order->no);

            // 发送取消通知
            if (env('SEND_WECHAT_MSG')) {
                $this->app->template_message->send([
                    'touser' => $order->user->mp_open_id,
                    'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                    'url' => env('APP_URL') . '/wechat/recover_order/' . $order->no,
                    'data' => [
                        'first' => '卖书订单已取消',
                        'keyword1' => $order->no,
                        'keyword2' => '已取消',
                        'keyword3' => Carbon::now()->toDateTimeString()
                    ]
                ]);
            }
        }
    }
}
