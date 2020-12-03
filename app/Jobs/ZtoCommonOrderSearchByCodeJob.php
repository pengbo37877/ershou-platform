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

class ZtoCommonOrderSearchByCodeJob implements ShouldQueue
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
        $request->setUrl("http://58.40.16.122:8080/commonOrderSearchbycode");

        //build data;
        $data = [
            "orderCode" => [
                "190628000033002108",
            ],
            "sendId" => "130520142010"
        ];

        $dataStr = json_encode($data);
        Log::info("ZtoCommonOrderSearchByCodeJob data=".$dataStr);
        $request->setData($dataStr);
        $result = $client->execute($request);
        Log::info('ZtoCommonOrderSearchByCodeJob '.$result);
    }
}
