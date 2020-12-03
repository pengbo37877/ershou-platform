<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use EasyWeChat\OfficialAccount\Application;
use EasyWeChat\Payment\Application as WxPayment;

class PaymentController extends Controller
{
    public $app,$payment;

    public function __construct(Application $app,  WxPayment $payment) {
        $this->app = $app;
        $this->payment = $payment;
    }

    public function transferToWx()
    {
        $this->payment->transfer->toBalance([
            'partner_trade_no' => '1233455', // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => '王小帅', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => 10, // 企业付款金额，单位为分
            'desc' => '提现', // 企业付款操作说明信息。必填
        ]);
        return $this->payment->transfer->queryBalanceOrder('1233455');
    }
}
