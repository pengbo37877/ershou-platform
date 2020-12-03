<?php

namespace App\Http\Controllers;

use App\Events\OrderDelivered;
use App\Order;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function array()
    {
        return view('array');
    }

    public function zto()
    {
        return view('zto.uploader');
    }

    public function uploadZto(Request $request)
    {
        if (!$request->hasFile('file')){
            return response()->json(['msg' => '没有xslx', 'code'=> 500]);
        }
        Storage::disk('public')->delete('zto.xlsx');
        $file = $request->file('file');
        $msg = "";
        if ($file->isValid()) {
            $path = $file->getRealPath();
            Storage::disk('public')->put('zto.xlsx', file_get_contents($path));

            $rows = Excel::selectSheetsByIndex(0)->load('public/storage/zto.xlsx', function($reader){})
                ->get(['订单号', '运单编号'])->toArray();
            Log::info(json_encode($rows, JSON_UNESCAPED_UNICODE));
            foreach ($rows as $row) {
                $order = Order::where('no', $row['订单号'])->first();
                if ($order && empty($order->express) && empty($order->express_no)) {
                    $order->express = 'ZTO';
                    $order->express_no = $row['运单编号'];
                    $order->save();
                }
            }
        }

        return response()->json(['msg' => $msg]);
    }
}
