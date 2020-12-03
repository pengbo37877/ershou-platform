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

class ZtoOpenOrderCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $order;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order = null)
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
        $properties = new ZopProperties("kfpttestCode", "kfpttestkey==");
        $client = new ZopClient($properties);
        $request = new ZopRequest();
        $request->setUrl("http://58.40.16.122:8080/OpenOrderCreate");

        //build data;
        $data = [
            "partnerCode" => "130520142013234", // order->no
            "type" => 1,
            "sender" => [
                "id" => "130520142010",
                "name" => "李琳",
                "mobile" => "13912345678",
                "prov" => "上海市",
                "city" => "上海市",
                "county" => "青浦区",
                "address" => "华新镇华志路123号",
            ],
            "receiver" => [
                "id" => "130520142097",
                "name" => "杨逸嘉",
                "mobile" => "13687654321",
                "prov" => "四川省",
                "city" => "成都市",
                "county" => "武侯区",
                "address" => "育德路497号",
            ]
        ];
        $dataStr = json_encode(["orderGroup" => $data]);
        Log::info("ZtoOpenOrderCreateJob data=".$dataStr);
        $request->setData($dataStr);
        $result = $client->execute($request);
        Log::info('ZtoOpenOrderCreateJob '.$result);
        $resultObj = json_decode($result, true);
        if (isset($resultObj['result']) && isset($resultObj['result']['orderCode'])) {
            Log::info('ZtoOpenOrderCreateJob orderCode=' . $resultObj['result']['orderCode']);
        }
    }
}
