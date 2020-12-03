<?php

namespace App\Jobs;

use App\Events\OrderCompleted;
use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Events\OrderSigned;
use App\Order;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class UpdateOrderShipData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        Log::info('UpdateOrderShipData 开始更新物流信息');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sale_orders = Order::where('type', Order::ORDER_TYPE_SALE)
            ->where('closed', false)->whereNotNull('express')
            ->whereNotNull('express_no')->where('sale_status', '>=', Order::SALE_STATUS_STOCK_OUT)
            ->where('created_at', '>', now()->subDays(7)->toDateTimeString())
            ->get();
        foreach ($sale_orders as $order) {
            $this->getShipData($order);
        }

        $recover_orders = Order::where('type', Order::ORDER_TYPE_RECOVER)->where('closed', false)->whereNotNull('express')
            ->whereNotNull('express_no')->where('recover_status', Order::RECOVER_STATUS_ARRANGE_EXPRESS)
            ->where('created_at', '>', now()->subDays(7)->toDateTimeString())
            ->get();
        foreach ($recover_orders as $order) {
            $this->getShipData($order);
        }
    }

    function getShipData($order)
    {
        if (!$order->express || !$order->express_no) {
            Log::info('job getShipData快递公司或者快递号不能为空');
            return null;
        }

        if ($order->ship_status == Order::SHIP_STATUS_RECEIVED && $order->ship_data) {
            Log::info('job getShipData 订单' . $order->no . '已签收，不需要再更新了');
            return null;
        }

        if ($order->type == Order::ORDER_TYPE_SALE && $order->sale_status == Order::SALE_STATUS_COMPLETE && $order->ship_data) {
            Log::info('job getShipData 订单' . $order->no . '已完成');
            return null;
        }

        if ($order->type == Order::ORDER_TYPE_RECOVER && $order->recover_status == Order::RECOVER_STATUS_COMPLETE && $order->ship_data) {
            Log::info('job getShipData 订单' . $order->no . '已完成');
            return null;
        }

        // 过期订单不发消息
        $validDatetime = now()->subDays(10)->toDateTimeString();
        if ($order->created_at < $validDatetime) {
            Log::info('job getShipData 订单' . $order->no . '已过期');
            return null;
        }

        $express_no = preg_replace('# #', '', $order->express_no);
        $requestData = "{'OrderCode':'','ShipperCode':'" . $order->express . "','LogisticCode':'" . $express_no . "'}";

        $datas = array(
            'EBusinessID' => '1583793',
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, 'df59c98a-8981-4508-98c9-15c67755ea2d');
        $result = $this->sendPost('http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx', $datas);

        //根据公司业务处理返回的信息......
        $shipData = json_decode($result, true);
        Log::info('job订单物流信息如下');
        Log::info($shipData);
        // 回流鱼卖书类的订单自动完成
        if ($order->type == Order::ORDER_TYPE_SALE && isset($shipData['State'])) {
            $state = $shipData['State'];
            if (intval($state) == 0) {
                // 在途中
                $order->ship_status = Order::SHIP_STATUS_DELIVERED;
                $order->sale_status = Order::SALE_STATUS_DELIVERED;
                $order->save();
                event(new OrderDelivered($order));
            } else if (intval($state) == 1) {
                // 在途中
                $order->ship_status = Order::SHIP_STATUS_DELIVERED;
                $order->sale_status = Order::SALE_STATUS_DELIVERED;
                $order->save();
                event(new OrderDelivered($order));
            } else if (intval($state) == 2) {
                // 在途中
                $order->ship_status = Order::SHIP_STATUS_DELIVERED;
                $order->sale_status = Order::SALE_STATUS_DELIVERED;
                $order->save();
                event(new OrderDelivered($order));
            } else if (intval($state) == 3) {
                // 已签收
                $order->ship_status = Order::SHIP_STATUS_RECEIVED;
                $order->sale_status = Order::SALE_STATUS_COMPLETE;
                $order->save();
                event(new OrderCompleted($order));
            }
        } else if ($order->type == Order::ORDER_TYPE_RECOVER && isset($shipData['State'])) {
            $state = $shipData['State'];
            if (intval($state) == 2) {
                // 在途中
                $order->ship_status = Order::SHIP_STATUS_DELIVERED;
                $order->recover_status = Order::RECOVER_STATUS_DELIVERED;
                $order->save();
                event(new OrderDelivered($order));
            } else if (intval($state) == 2) {
                // 在途中
                $order->ship_status = Order::SHIP_STATUS_DELIVERED;
                $order->recover_status = Order::RECOVER_STATUS_DELIVERED;
                $order->save();
            } else if (intval($state) == 3) {
                // 已签收
                if ($order->recover_status != Order::RECOVER_STATUS_COMPLETE) {
                    $order->ship_status = Order::SHIP_STATUS_RECEIVED;
                    $order->recover_status = Order::RECOVER_STATUS_PAYING;
                    $order->save();
                } else {
                    $order->ship_status = Order::SHIP_STATUS_RECEIVED;
                    $order->save();
                }
                // 给用户发送已签收通知
                event(new OrderSigned($order));
            }
        }
        $order->update([
            'ship_data' => json_encode($shipData)
        ]);

        return $result;
    }

    function sendPost($url, $datas)
    {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], isset($url_info['port']) ? $url_info['port'] : 80);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    function encrypt($data, $appkey)
    {
        return urlencode(base64_encode(md5($data . $appkey)));
    }
}
