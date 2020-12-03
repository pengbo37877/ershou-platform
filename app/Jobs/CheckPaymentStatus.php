<?php

namespace App\Jobs;

use App\Events\OrderPaid;
use App\Order;
use Carbon\Carbon;
use EasyWeChat\Factory;
use EasyWeChat\Payment\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CheckPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        Log::info('开始查看支付状态');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = [
            'sandbox'            => env('WECHAT_PAYMENT_SANDBOX', false),
            'app_id'             => env('WECHAT_PAYMENT_APPID', 'wxf829e81503ed614a'),
            'mch_id'             => env('WECHAT_PAYMENT_MCH_ID', '1494109692'),
            'key'                => env('WECHAT_PAYMENT_KEY', '123gvbjkh3124j312kljh4klh32klj4h'),
            'cert_path'          => env('WECHAT_PAYMENT_CERT_PATH', '/etc/wxpay/apiclient_cert.pem'),    // XXX: 绝对路径！！！！
            'key_path'           => env('WECHAT_PAYMENT_KEY_PATH', '/etc/wxpay/apiclient_key.pem'),      // XXX: 绝对路径！！！！
            'notify_url'         => env('APP_URL').'/payments/wechat-notify',                           // 默认支付结果通知地址
        ];
        $payment = Factory::payment($config);
        $orders = Order::where('type', Order::ORDER_TYPE_SALE)->where('sale_status', '<>', Order::SALE_STATUS_CANCEL)
            ->where('closed', 0)->whereNull('paid_at')->orderBy('created_at', 'desc')->take(5)->get();
        $orders->each(function ($order) use ($payment){
            $result = $payment->order->queryByOutTradeNumber($order->no);
            Log::info($result);
            // 修改订单状态
            if (isset($result['trade_state']) && $result['trade_state'] == 'SUCCESS') {
                $order->paid_at = Carbon::now();
                $order->payment_method = Order::PAYMENT_WECHAT;
                $order->sale_status = Order::SALE_STATUS_PAID;
                $order->save();
                event(new OrderPaid($order));
            }
        });
    }
}
