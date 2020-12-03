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

class ZtoSubmitOrderCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order=null)
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
        $request->setUrl("http://58.40.16.120:9001/submitOrderCode");

        //build data;
        $data = [
            'partner' => 'test',
            'verify' => 'ZTO123',
            'datetime' => now()->toDateTimeString()
        ];
        $content = [
            "id" => "xfs101100111011",
            "typeId" => "",
            "receiver"=> [
                "address"=>"建龙2期4栋",
                "city"=>"湖北省,武汉市,洪山区",
                "mobile"=>"13429893661",
                "name"=>"彭",
            ],
            "sender"=> [
                //"address"=>"光谷步行街老尚都1栋903",
                "address"=>"光谷大道58号红桃开集团电商仓库",
                "city"=>"湖北省,武汉市,洪山区",
                "mobile"=>"18310951930",
                "name"=>"回流鱼",
            ],
        ];
        $data['content'] = $content;
        $dataStr = json_encode($data);
        Log::info("ZtoSubmitOrderCodeJob data=".json_encode($data, JSON_UNESCAPED_UNICODE));
        $request->setData($dataStr);
        $result = $client->execute($request);
        Log::info('ZtoSubmitOrderCodeJob '.$result);
    }
}
