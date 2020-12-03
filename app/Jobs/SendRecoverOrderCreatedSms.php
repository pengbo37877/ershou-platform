<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendRecoverOrderCreatedSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $time;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone, $time)
    {
        $this->phone = $phone;
        $this->time = $time;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $text = "【回流鱼】你的卖书订单已通过审核，顺丰快递将于你预约的时间：" . $this->time . "上门取书，请提前装箱。";
        $param = [
            'apikey' => '05cbfd8cf9511b607c7e54d49ecfef60',
            'mobile' => $this->phone,
            'text' => $text
        ];
        $client = new Client();
        $res = $client->post('https://sms.yunpian.com/v2/sms/single_send.json', [
            'headers' => [
                'Accept'        => 'application/json;charset=utf-8',
                'Content-Type'  => 'application/x-www-form-urlencoded;charset=utf-8'
            ],
            'form_params' => $param
        ]);
        Log::info('SendRecoverOrderCreatedSms res=' . json_encode($res, JSON_UNESCAPED_UNICODE));
    }
}
