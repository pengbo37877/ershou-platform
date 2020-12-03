<?php

namespace App\Jobs;

use App\Order;
use App\Zto\ZopClient;
use App\Zto\ZopProperties;
use App\Zto\ZopRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ZtoExposeServicePushOrderServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

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
        if ($this->order->sale_status == Order::SALE_STATUS_ORDERED_EXPRESS) {
            return;
        }
        if (!empty($this->order->express) || !empty($this->order->express_no)) {
            return;
        }
        $properties = new ZopProperties("dae66ce5e08b445098f1be408a232834", "aa29ef5a1246");
        $client = new ZopClient($properties);
        $request = new ZopRequest();
        $request->setUrl("http://japi.zto.cn/exposeServicePushOrderService");

        $address = $this->order->address;
        //build data;
        $data = [
            "shopKey" => "NTUzRjREMDZGMDlFNzYyRkE5MTU4RTM2MkNGOENEN0Q=",
            'orderId' => $this->order->no,
            "orderType" => "0",
            "receiveAddress"=> $address->address,
            "receiveCity"=> $address->city,
            "receiveCounty"=> $address->district,
            "receiveMan"=> $address->contact_name,
            "receiveMobile"=> $address->contact_phone,
            "receiveProvince"=> $address->province,
            //"sendAddress"=>"光谷步行街老尚都1栋903",
            "sendAddress"=>"光谷大道58号红桃开集团电商仓库",
            "sendCity"=>"武汉市",
            "sendCompany"=>"回流鱼",
            "sendCounty"=>"洪山区",
            "sendMan"=>"回流鱼",
            "sendMobile"=>"18310951930",
            "sendProvince"=>"湖北省",
            "orderDate" => now()->toDateTimeString(),

        ];

        $dataStr = json_encode(['data' => $data]);
        Log::info("ZtoExposeServicePushOrderServiceJob data=".$dataStr);
        $request->setData($dataStr);
        $result = $client->execute($request);
        Log::info('ZtoExposeServicePushOrderServiceJob '.$result);
        $resultArray = json_decode($result, true);
        if (isset($resultArray['statusCode']) && $resultArray['statusCode'] == 'A200') {
            $this->order->sale_status = Order::SALE_STATUS_ORDERED_EXPRESS;
            $this->order->save();
        }
    }
}
