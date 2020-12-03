<?php

namespace App\Admin\Controllers;

use App\Book;
use App\BookSku;
use App\Http\Controllers\Controller;
use App\Statistic;
use Carbon\Carbon;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StsController extends Controller
{
    function secToTime($times){
        $result = '00:00:00';
        if ($times>0) {
            $day = floor($times/86400);
            $hour = floor(($times-86400*$day)/3600);
            $minute = floor(($times-86400*$day-3600 * $hour)/60);
            $second = floor($times % 60);
            $result = $day."天 ".$hour.':'.$minute.':'.$second;
        }
        return $result;
    }

    public function index(Content $content)
    {
        $book_id = 1;
        return Admin::content(function (Content $content) use ($book_id) {
            $date = date('Y-m-d',time()-86400);
            $datas = Statistic::orderBy('date','desc')->first();
            $seven_ago = date('Y-m-d',time()-8*86400);
            $line_datas = Statistic::whereBetween('date',[$seven_ago." 00:00:00",$date." 23:59:59"])->orderBy('date')->get();
            $data = array();
            $data1 = array();
            $date_arr = array();
            foreach ($line_datas as $line_data){
                array_push($date_arr,$line_data->date);
                array_push($data,$line_data->sold);
                array_push($data1,$line_data->sku_count);
            }
            $date_arr = json_encode($date_arr);
            $data = json_encode($data);
            $data1 = json_encode($data1);
            $homeView = view('admin.admin2',compact('datas','data','data1','date_arr'))
                ->render();
            $content->header('统计');
            $content->description('回流鱼数据');
            $content->row($homeView);
        });
    }

    public function getSFList(){
        $sfs = Statistic::where('sf_money','>',0)->select(['sf_money','sf_mark','SF_amount','sf_update'])->get();
        return $sfs;
    }

    public function updateShip(Request $request){
        $name = $request->get('name');
        $money = $request->get('money');
        $mark = $request->get('mark');
        if($name=="zto"){
            $sts = Statistic::orderBy('id','desc')->first();
            $sts->zto_money = $money;
            $sts->zto_mark = $mark;
            $sts->ZTO_amount = $sts->ZTO_amount+$money;
            $sts->save();
            return response()->json([
                'code'=>200,
                'msg'=>"刷新页面查看"
            ]);
        }elseif ($name=="sf"){
            $sts = Statistic::orderBy('id','desc')->first();
            $sts->sf_money = $money;
            $sts->sf_mark = $mark;
            $sts->SF_amount = $sts->SF_amount+$money;
            $sts->save();
            return response()->json([
                'code'=>200,
                'msg'=>"刷新页面查看"
            ]);
        }else{
            return response()->json([
                'code'=>500,
                'msg'=>"只能更新中通和顺丰"
            ]);
        }
    }

    public function index2(Content $content)
    {
        $date = date('Y-m-d',time()-86400);
        $datas = Statistic::where('date',$date)->first();
        $seven_ago = date('Y-m-d',time()-8*86400);
        $line_datas = Statistic::whereBetween('date',[$seven_ago." 00:00:00",$date." 23:59:59"])->orderBy('date')->get();
        $data = array();
        $data1 = array();
        $date_arr = array();
        foreach ($line_datas as $line_data){
            array_push($date_arr,$line_data->date);
            array_push($data,$line_data->sold);
            array_push($data1,$line_data->sku_count);
        }
        $date_arr = json_encode($date_arr);
        $data = json_encode($data);
        $data1 = json_encode($data1);
        return view('admin',compact('datas','data','data1','date_arr'));
    }
}
