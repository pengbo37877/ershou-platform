<?php

namespace App\Listeners;

use App\Events\BookShipper;
use App\Order;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class BookShipperListener
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
     * @param  BookShipper  $event
     * @return void
     */
    public function handle(BookShipper $event)
    {
        $banUserID = [1,9290,9306,26535,27308,27430,41835,42307,87216,86723];
        $cities = ["石家庄市","沈阳市","哈尔滨市","杭州市","福州市","济南市","广州市","武汉市","成都市","昆明市","兰州市","台北市",
            "南宁市","银川市","太原市","长春市","南京市","合肥市","南昌市","郑州市","长沙市","海口市","贵阳市","西安市","西宁市",
            "呼和浩特市","拉萨市","乌鲁木齐市","北京市","上海市","重庆市","天津市","深圳市"];
        $order = $event->order;
        $address = $order->address;
        $canBook = $order->closed==Order::PAYING_STATUS_OPEN && is_null($order->express) && is_null($order->express_no);
        if ($canBook) {
            if ($order->type == Order::ORDER_TYPE_RECOVER) {
                // 叫顺丰
                if (count(array_intersect([$order->user_id], $banUserID))>0) {
                    Log::info('不要意思，用户 '.$order->user_id.' 被禁止卖书了');
//                }else if(in_array($address->city, $cities)) {
//                    $this->bookDbl($order);
                }else{
                    $this->bookSF($order);
                }
            }else if ($order->type == Order::ORDER_TYPE_SALE) {
                // 叫中通
                Log::info('是不是很想叫中通上门啊');
            }
        }
    }

    // 收货地址是回流鱼，支付方式是到付
    function bookSf($order) {
        if ($order->express || $order->express_no) {
            Log::info('叫快递失败，你貌似已经叫了 order no='.$order->no);
            return null;
        }
        //请求url，接口正式地址：http://api.kdniao.cc/api/eorderservice    测试环境地址：http://testapi.kdniao.cc:8081/api/oorderservice
        //构造在线下单提交信息
        $eorder = [];
        $eorder["ShipperCode"] = 'SF';
        $eorder["OrderCode"] = $order->no;
        $eorder["PayType"] = 2; // 到付
        $eorder["ExpType"] = 2; // 顺丰特惠
        $sender = [];
        $sender["Name"] = $order->address->contact_name;
        $sender["Mobile"] = $order->address->contact_phone;
        $sender["ProvinceName"] = $order->address->province;
        $sender["CityName"] = $order->address->city;
        $sender["ExpAreaName"] = $order->address->district;
        if (count($order->items)>=30) {
            $sender["Address"] = $order->address->address."(备注：请发重货包裹)";
        }else{
            $sender["Address"] = $order->address->address."(备注：请发顺丰特惠)";
        }

        $receiver = [];
        $receiver["Name"] = "回流鱼";
        $receiver["Mobile"] = "18310951930";
        $receiver["ProvinceName"] = "湖北省";
        $receiver["CityName"] = "武汉市";
        $receiver["ExpAreaName"] = "洪山区";
        if (count($order->items)>=30) {
            //$receiver["Address"] = "尚都1栋9层903 (备注：请发重货包裹)";
            $receiver["Address"] = "光谷大道58号红桃开集团电商仓库 (备注：请发重货包裹)";
        }else{
            //$receiver["Address"] = "尚都1栋9层903 (备注：请发顺丰特惠)";
            $receiver["Address"] = "光谷大道58号红桃开集团电商仓库 (备注：请发顺丰特惠)";
        }

        $commodityOne = [];
        $commodityOne["GoodsName"] = "书本";
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;
        $eorder["StartDate"] = Carbon::createFromTimeString($order->recover_time)->toDateTimeString();
        $eorder["EndDate"] = Carbon::createFromTimeString($order->recover_time)->addHour()->toDateTimeString();

        Log::info($eorder);
        //调用在线下单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);
        $jsonResult = $this->submitOOrder($jsonParam);

        //解析在线下单返回结果
        $result = json_decode($jsonResult, true);
        if($result["ResultCode"] == "100") {
            if ($result["Success"]) {
                Log::info($result);
                $order->update([
                    'express' => 'SF',
                    'express_no' => $result['Order']['LogisticCode'],
                    'recover_status' => Order::RECOVER_STATUS_ARRANGE_EXPRESS
                ]);
            }
        }
        else {
            Log::info('book在线下单失败');
            event(new BookShipper($order));
        }
    }

    // 德邦物流
    function bookDbl($order) {
        if ($order->express || $order->express_no) {
            Log::info('叫快递失败，你貌似已经叫了 order no='.$order->no);
            return null;
        }
        //请求url，接口正式地址：http://api.kdniao.cc/api/eorderservice    测试环境地址：http://testapi.kdniao.cc:8081/api/oorderservice
        //构造在线下单提交信息
        $eorder = [];
        $eorder["ShipperCode"] = 'DBL';
        $eorder["OrderCode"] = $order->no;
        $eorder["PayType"] = 2; // 到付
        $eorder["ExpType"] = 2; // 360特惠
        $sender = [];
        $sender["Name"] = $order->address->contact_name;
        $sender["Mobile"] = $order->address->contact_phone;
        $sender["ProvinceName"] = $order->address->province;
        $sender["CityName"] = $order->address->city;
        $sender["ExpAreaName"] = $order->address->district;
        $sender["Address"] = $order->address->address."(备注：请发360特惠件)";

        $receiver = [];
        $receiver["Name"] = "回流鱼";
        $receiver["Mobile"] = "18310951930";
        $receiver["ProvinceName"] = "湖北省";
        $receiver["CityName"] = "武汉市";
        $receiver["ExpAreaName"] = "洪山区";
        //$receiver["Address"] = "尚都1栋9层903 (备注：请发360特惠件)";
        $receiver["Address"] = "光谷大道58号红桃开集团电商仓库 (备注：请发360特惠件)";

        $commodityOne = [];
        $commodityOne["GoodsName"] = "书本";
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;
        $eorder["StartDate"] = Carbon::createFromTimeString($order->recover_time)->toDateTimeString();
        $eorder["EndDate"] = Carbon::createFromTimeString($order->recover_time)->addHour()->toDateTimeString();

        Log::info($eorder);
        //调用在线下单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);
        $jsonResult = $this->submitOOrder($jsonParam);

        //解析在线下单返回结果
        $result = json_decode($jsonResult, true);
        if($result["ResultCode"] == "100") {
            if ($result["Success"]) {
                Log::info("DBL=");
                Log::info($result);
                $order->update([
                    'express' => 'DBL',
                    'express_no' => $result['Order']['LogisticCode'],
                    'recover_status' => Order::RECOVER_STATUS_ARRANGE_EXPRESS
                ]);
            }
        }
        else {
            Log::info('book在线下单失败');
            event(new BookShipper($order));
        }
    }

    function submitOOrder($requestData){
        $datas = array(
            'EBusinessID' => '1583793',
            'RequestType' => '1001',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2', // 到付
        );
        $datas['DataSign'] = $this->encrypt($requestData, 'df59c98a-8981-4508-98c9-15c67755ea2d');
        $result=$this->sendPost('http://api.kdniao.com/api/eorderservice', $datas);
//        $result=$this->sendPost('http://api.kdniao.cc/api/OOrderService', $datas);

        //根据公司业务处理返回的信息......
        Log::info('叫快递的结果');
        Log::info($result);

        return $result;
    }

    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], isset($url_info['port'])?$url_info['port']:80);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
}
