<?php

namespace App\Console\Commands;

use App\Book;
use App\BookSku;
use App\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Statistic;
use Illuminate\Support\Facades\DB;

class DailyStsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getData($data){
        $json = array();
        $sum = 0;
        $count = 0;
        foreach ($data as $item){
            $sum += $item->s;
            $count += $item->c;
        }
        $json["sum"] = $sum;
        $json["count"] = $count;
        $json["avg"] = round($sum/$count,2);
        return $json;
    }

    public function getAreaData($data,$label=[0,0,0,0,0,0,0]){
        $json = array();
        $count = 0;
        foreach ($data as $item){
            if($item->d < 1){
                $label[0] += $item->c;
            }elseif ($item->d >= 1 && $item->d < 5){
                $label[1] += $item->c;
            }elseif ($item->d >= 5 && $item->d < 10){
                $label[2] += $item->c;
            }elseif ($item->d >= 10 && $item->d < 30){
                $label[3] += $item->c;
            }elseif ($item->d >= 30 && $item->d < 100){
                $label[4] += $item->c;
            }elseif ($item->d >= 100 && $item->d < 180){
                $label[5] += $item->c;
            }elseif ($item->d >= 180){
                $label[6] += $item->c;
            }
            $count += $item->c;
        }
        $json["data"] = $label;
        $json["count"] = $count;
        return $json;
    }

    public function getRecoverAreaData($data,$label=[0,0,0,0,0,0,0]){
        $json = array();
        $count = 0;
        foreach ($data as $item){
            if($item->p < 1){
                $label[0] += $item->c;
            }elseif ($item->p >= 1 && $item->p < 2){
                $label[1] += $item->c;
            }elseif ($item->p >= 2 && $item->p < 3){
                $label[2] += $item->c;
            }elseif ($item->p >= 3 && $item->p < 5){
                $label[3] += $item->c;
            }elseif ($item->p >= 5 && $item->p < 10){
                $label[4] += $item->c;
            }elseif ($item->p >= 10 && $item->p < 30){
                $label[5] += $item->c;
            }elseif ($item->p >= 30){
                $label[6] += $item->c;
            }
            $count += $item->c;
        }
        $json["data"] = $label;
        $json["count"] = $count;
        return $json;
    }

    public function getSaleAreaData($data,$label=[0,0,0,0,0,0,0]){
        $json = array();
        $count = 0;
        foreach ($data as $item){
            if($item->p < 5){
                $label[0] += $item->c;
            }elseif ($item->p >= 5 && $item->p < 10){
                $label[1] += $item->c;
            }elseif ($item->p >= 10 && $item->p < 15){
                $label[2] += $item->c;
            }elseif ($item->p >= 15 && $item->p < 20){
                $label[3] += $item->c;
            }elseif ($item->p >= 20 && $item->p < 50){
                $label[4] += $item->c;
            }elseif ($item->p >= 50 && $item->p < 100){
                $label[5] += $item->c;
            }elseif ($item->p >= 100){
                $label[6] += $item->c;
            }
            $count += $item->c;
        }
        $json["data"] = $label;
        $json["count"] = $count;
        return $json;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $today = date('Y-m-d', time());
        $data = Statistic::whereBetween('date',[$today,$today." 23:59:59"])->get();
        if(count($data)==0){
            Statistic::insert(['date'=>Carbon::now()]);
        }
        $sold_data = DB::select("SELECT count(*) c,floor(soldtime/86400) d FROM book_skus where status=4 and sale_at<sold_at
group by floor(soldtime/86400)");
        $sale_data = DB::select("SELECT count(*) c,floor(timestampdiff(SECOND,sale_at,now())/86400) d FROM book_skus where status=1
group by floor(timestampdiff(SECOND,sale_at,now())/86400)");
        $recover_data = DB::select("SELECT count(*) c,CEILING(recover_price) p,sum(recover_price) s FROM book_skus where recover_price>0 
group by CEILING(recover_price)");
        $sales_data = DB::select("SELECT count(*) c,CEILING(price) p,sum(price) s FROM book_skus where status=4 and sale_at<sold_at 
group by CEILING(price)");
        $book_count = Book::count();
        $sku_count = BookSku::count();
        $issue_count = BookSku::where('status',8)->count();
        $sale_order_count = Order::where('closed',0)->where('type',2)->where('sale_status',70)->count();
        $recover_order_count = Order::where('closed',0)->where('type',1)->where('recover_status',70)->count();
        $sold_count = 0;
        foreach ($sold_data as $item){
            $sold_count += $item->c;
        }
        $median = DB::select("SELECT soldtime FROM book_skus where status=4 and sale_at<sold_at order by soldtime limit ?,1",[round($sold_count/2,0)]);
        $recover_json = self::getData($recover_data);
        $sales_json = self::getData($sales_data);
//        $zto_data = Statistic::where('ZTO_amount','>',0)->orderBy('date','desc')->first();
//        $sf_data = Statistic::where('SF_amount','>',0)->orderBy('date','desc')->first();
        $data = Statistic::orderBy('date','desc')->first();
        $data->sold_data = json_encode($sold_data);
        $data->sold_linedata = json_encode($this->getAreaData($sold_data)["data"]);
        $data->sale_data = json_encode($sale_data);
        $data->sale_linedata = json_encode($this->getAreaData($sale_data)["data"]);
        $data->book_count = $book_count;
        $data->sku_count = $sku_count;
        $data->issue_count = $issue_count;
        $data->sale_order_count = $sale_order_count;
        $data->recover_order_count = $recover_order_count;
        $data->median = $median[0]->soldtime;
        $data->sales_data = json_encode($sales_data);
        $data->sales_linedata = json_encode($this->getSaleAreaData($sales_data)["data"]);
        $data->recover_data = json_encode($recover_data);
        $data->recover_linedata = json_encode($this->getRecoverAreaData($recover_data)["data"]);
        $data->recover_amount = $recover_json["sum"];
        $data->sold_amount = $sales_json["sum"];
        $data->recover_avg = round($recover_json["sum"]/$sku_count,2);
        $data->sold_avg = round($sales_json["sum"]/$sold_count,2);
        $data->sold_count = $sold_count;
//        $data->ZTO_amount = $zto_data ? $zto_data->ZTO_amount:0;
//        $data->SF_amount = $sf_data ? $sf_data->SF_amount:0;
        $data->save();
    }
}
