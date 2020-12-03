<?php

namespace App\Jobs;

use App\Zto\ZopClient;
use App\Zto\ZopProperties;
use App\Zto\ZopRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ZtoSubBillLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $request->setUrl("http://58.40.16.120:9001/subBillLog");

        //build data;
        $data = [];
        $bill = [
            "id" => "1111111111",
            "billCode" => "680000000020",
            "pushCategory" => "callBack",
            "pushTarget" => "https://huiliuyu.com/ship/zto_callback",
            "pushTime" => 1,
            "subscriptionCategory" => 63,
            "createBy" => "test"
        ];
        $bill2 = [
            "id" => "1111111112",
            "billCode" => "680000000022",
            "pushCategory" => "callBack",
            "pushTarget" => "https://huiliuyu.com/ship/zto_callback",
            "pushTime" => 1,
            "subscriptionCategory" => 63,
            "createBy" => "test"
        ];
        array_push($data, $bill);
        array_push($data, $bill2);
        $dataStr = json_encode($data);
        Log::info("ZtoSubBillLogJob data=".$dataStr);
        $request->setData($dataStr);
        $result = $client->execute($request);
        Log::info('ZtoSubBillLogJob '.json_encode($result, JSON_UNESCAPED_UNICODE));
    }
}
