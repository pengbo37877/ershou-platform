<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendRecoverOrderCreatedSms;
use App\Order;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use GuzzleHttp\Client;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderCreatedListener
{
    protected $app;

    /**
     * Create the event listener.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the event.
     *
     * @param  OrderCreated  $event
     * @return void
     */
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        $user = $order->user;
        if ($order->type == Order::ORDER_TYPE_RECOVER) {
            // 回收类订单

            try {
                if (env('SEND_WECHAT_MSG')) {
                    $this->app->template_message->send([
                        'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/recover_order/' . $order->no,
                        'data' => [
                            'first' => $user->nickname . ' 要卖' . count($order->items) . '本书给回流鱼',
                            'keyword1' => $order->no,
                            'keyword2' => '等什么？还不去后台审核',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                }
            } catch (InvalidArgumentException $e) {
            }

            // 给用户发短信
            $address = $order->address;
            $time = Carbon::createFromTimeString($order->recover_time);
            $month = $time->month;
            $day = $time->day;
            $hour = $time->hour;
            $next_hour = $hour+1;
            if ($time->isToday()) {
                if ($hour<12) {
                    $time = '今天上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = '今天中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = '今天下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if ($time->isTomorrow()) {
                if ($hour<12) {
                    $time = '明天上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = '明天中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = '明天下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if ($time->isMonday()) {
                if ($hour<12) {
                    $time = $month.'月'.$day.'日 周一上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = $month.'月'.$day.'日 周一中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = $month.'月'.$day.'日 周一下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if($time->isTuesday()) {
                if ($hour<12) {
                    $time = $month.'月'.$day.'日 周二上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = $month.'月'.$day.'日 周二中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = $month.'月'.$day.'日 周二下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if($time->isWednesday()) {
                if ($hour<12) {
                    $time = $month.'月'.$day.'日 周三上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = $month.'月'.$day.'日 周三中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = $month.'月'.$day.'日 周三下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if($time->isThursday()) {
                if ($hour<12) {
                    $time = $month.'月'.$day.'日 周四上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = $month.'月'.$day.'日 周四中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = $month.'月'.$day.'日 周四下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if($time->isFriday()) {
                if ($hour<12) {
                    $time = $month.'月'.$day.'日 周五上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = $month.'月'.$day.'日 周五中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = $month.'月'.$day.'日 周五下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if($time->isSaturday()) {
                if ($hour<12) {
                    $time = $month.'月'.$day.'日 周六上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = $month.'月'.$day.'日 周六中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = $month.'月'.$day.'日 周六下午'.$hour.'点-'.$next_hour.'点';
                }
            } else if($time->isSunday()) {
                if ($hour<12) {
                    $time = $month.'月'.$day.'日 周日上午'.$hour.'点-'.$next_hour.'点';
                }else if($hour==12) {
                    $time = $month.'月'.$day.'日 周日中午'.$hour.'点-'.$next_hour.'点';
                }else{
                    $time = $month.'月'.$day.'日 周日下午'.$hour.'点-'.$next_hour.'点';
                }
            } else{
                $time = $time->toDateTimeString();
            }
//            SendRecoverOrderCreatedSms::dispatch($address->contact_phone, $time)->delay(now()->addSecond());
        }else if($order->type == Order::ORDER_TYPE_SALE) {
            // 卖书类订单
            $info = $order->allitems->map(function ($item) {
                return $item->book->name.' | '.$item->bookSku->hly_code.' | '.$item->bookSku->title;
            });
            // 给雪亮发消息
            try {
                if (env('SEND_WECHAT_MSG')) {
                    $this->app->template_message->send([
                        'touser' => 'ojrK40eBFLP_LWoqF6lCtyB7sIL0',
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                        'data' => [
                            'first' => $user->nickname . ' 买了' . count($order->allitems) . '本书',
                            'keyword1' => $order->no,
                            'keyword2' => $info->implode('\n'),
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                }
            } catch (InvalidArgumentException $e) {
            }
            // 给魏总发消息
            try {
                if (env('SEND_WECHAT_MSG')) {
                    $this->app->template_message->send([
                        'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                        'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                        'data' => [
                            'first' => $user->nickname . ' 买了' . count($order->allitems) . '本书',
                            'keyword1' => $order->no,
                            'keyword2' => '等什么？还不去准备发货',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                }
            } catch (InvalidArgumentException $e) {
            }
        }
    }
}
