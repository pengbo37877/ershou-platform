<?php

namespace App\Jobs;

use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SubscribeOrderShipData implements ShouldQueue
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
        if (empty($this->order->express) || empty($this->order->express_no)) {
            return;
        }
        $this->orderTracesSubByJson();
    }

    /**
     * Json方式  物流信息订阅
     */
    function orderTracesSubByJson(){
        $address = $this->order->address;
        $requestData= "{'ShipperCode':'".$this->order->express."',".
            "'LogisticCode':'".$this->order->express_no."'}";


        $datas = array(
            'EBusinessID' => '1583793',
            'RequestType' => '1008',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData);
        $result=$this->sendPost('http://api.kdniao.com/api/dist', $datas);
        Log::info('SubscribeOrderShipData data '.$this->order->id);
        Log::info($datas);

        //根据公司业务处理返回的信息......
        $array = json_decode($result, true);
        if (is_array($array)) {
            if (isset($array['Success']) && $array['Success']) {
                $this->order->extra = 'subscribed';
                $this->order->save();
            }else{
                Log::info('SubscribeOrderShipData '.$this->order->id);
                Log::info($result);
            }
        }

        return $result;
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
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

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data) {
        return urlencode(base64_encode(md5($data.'df59c98a-8981-4508-98c9-15c67755ea2d')));
    }
}
