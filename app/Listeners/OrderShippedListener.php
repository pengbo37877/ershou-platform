<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Order;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderShippedListener
{
    public $app;

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
     * @param  OrderShipped  $event
     * @return void
     */
    public function handle(OrderShipped $event)
    {
        $banUserID = [1,9290,9306,26535,27308,27430,41835,42307,87216,86723];
        $order = $event->order;
        Log::info('订单发货'.$order->no);
        if ($order->type == Order::ORDER_TYPE_RECOVER) {
            // 预约取件
            Log::info('预约取件'.$order->no);
            if (count(array_intersect([$order->user_id], $banUserID))>0) {
                Log::info('不要意思，用户'.$order->user_id.'被禁止卖书了');
            }else{
                $this->bookSF($order);
            }
        }
    }

    // 订阅订单轨迹数据
    function subscribe($order){
        if (!$order->express || !$order->express_no) {
            Log::info('subscribe快递公司或者快递号不能为空');
            return null;
        }
        $requestData="{'OrderCode': '".$order->no."',".
            "'ShipperCode':'".$order->express."',".
            "'LogisticCode':'".$order->express_no."',".
            "'PayType':1,".
            "'ExpType':1,".
            "'IsNotice':0,".
            "'Cost':1.0,".
            "'OtherCost':1.0,".
            "'Sender':".
            "{".
            "'Company':'武汉梧桐讯科科技有限公司','Name':'涂先生','Mobile':'18310951930','ProvinceName':'湖北省','CityName':'武汉市','ExpAreaName':'洪山区','Address':'世界城广场新尚都2栋14层21425'},".
            "'Receiver':".
            "{".
            "'Name':'".$order->address->contact_name."','Mobile':'".$order->address->contact_phone."','ProvinceName':'".
            $order->address->province."','CityName':'".$order->address->city."','ExpAreaName':'".$order->address->district."','Address':'".$order->address->address."'},".
            "'Commodity':".
            "[{'GoodsName':'书本'}]".
            "}";

        Log::info($requestData);
        $datas = array(
            'EBusinessID' => "1583793",
            'RequestType' => '1008',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, "df59c98a-8981-4508-98c9-15c67755ea2d");
        $result=$this->sendPost("http://api.kdniao.com/api/dist", $datas);

        //根据公司业务处理返回的信息......
        Log::info('订阅订单轨迹数据');
        Log::info($result);

        return $result;
    }

    // 获取订单轨迹数据
    function getShipData($order){
        if (!$order->express || !$order->express_no) {
            Log::info('getShipData快递公司或者快递号不能为空');
            return null;
        }

        $requestData= "{'OrderCode':'','ShipperCode':'".$order->express."','LogisticCode':'".$order->express_no."'}";

        $datas = array(
            'EBusinessID' => '1583793',
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, 'df59c98a-8981-4508-98c9-15c67755ea2d');
        $result=$this->sendPost('http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx', $datas);

        //根据公司业务处理返回的信息......
        $order->ship_data = $result;
        $order->save();
        Log::info('订单物流信息如下');
        Log::info($result);

        return $result;
    }

    // 叫顺丰
    function bookSF($order) {
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
        $eorder["ExpType"] = 1;
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
                    'express_no' => $result['Order']['LogisticCode']
                ]);
            }
        }
        else {
            Log::info('book在线下单失败');
        }
    }

    function bookEms($order) {
        if ($order->express || $order->express_no) {
            Log::info('叫快递失败，你貌似已经叫了 order no='.$order->no);
            return null;
        }
        //请求url，接口正式地址：http://api.kdniao.cc/api/eorderservice    测试环境地址：http://testapi.kdniao.cc:8081/api/oorderservice
        //构造在线下单提交信息
        $eorder = [];
        $eorder["ShipperCode"] = 'EMS';
        $eorder["OrderCode"] = $order->no;
        $eorder["PayType"] = 2; // 到付
        $eorder["ExpType"] = 8;
        $sender = [];
        $sender["Name"] = $order->address->contact_name;
        $sender["Mobile"] = $order->address->contact_phone;
        $sender["ProvinceName"] = $order->address->province;
        $sender["CityName"] = $order->address->city;
        $sender["ExpAreaName"] = $order->address->district;
        $sender["Address"] = $order->address->address;

        $receiver = [];
        $receiver["Name"] = "涂先生";
        $receiver["Mobile"] = "18310951930";
        $receiver["ProvinceName"] = "湖北省";
        $receiver["CityName"] = "武汉市";
        $receiver["ExpAreaName"] = "洪山区";
        $receiver["Address"] = "尚都1栋9层903";

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
        Log::info($result);
        if($result["ResultCode"] == "100") {
            if ($result["Success"]) {
                Log::info($result);
                $order->update([
                    'express' => 'EMS',
                    'express_no' => $result['Order']['KDNOrderCode']
                ]);
            }
        }
        else {
            Log::info('book在线下单失败');
        }
    }

    function cancelSF($order) {
        $eorder = [];
        $eorder["ShipperCode"] = 'SF';
        $eorder["OrderCode"] = $order->no;
        //调用在线下单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);
        $jsonResult = $this->cancelOOrder($jsonParam);
        Log::info($jsonResult);
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

        //根据公司业务处理返回的信息......
        Log::info('叫快递的结果');
        Log::info($result);

        return $result;
    }

    function cancelOOrder($requestData){
        $datas = array(
            'EBusinessID' => '1583793',
            'RequestType' => '1004',
            'RequestData' => urlencode($requestData) ,
        );
        $datas['DataSign'] = $this->encrypt($requestData, 'df59c98a-8981-4508-98c9-15c67755ea2d');
        $result=$this->sendPost('http://api.kdniao.com/api/eorderservice', $datas);

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
