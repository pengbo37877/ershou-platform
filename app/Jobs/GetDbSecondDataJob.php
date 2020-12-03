<?php

namespace App\Jobs;

use App\Book;
use App\BookPrice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use phpspider\core\selector;

class GetDbSecondDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subjectid;
    protected $times;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($subjectid, $times=1)
    {
        $this->subjectid = $subjectid;
        $this->times = $times;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (is_null($this->subjectid) || empty($this->subjectid)) {
            return;
        }
        if ($this->times >= 3){
            return;
        }
        $book = Book::where('subjectid', $this->subjectid)->first();
        if ($book) {
            $book_prices_count = BookPrice::where('book_id', $book->id)->count();
            if ($book_prices_count>0){
                return;
            }
        }else{
            Log::info('没有找到subjectid='.$this->subjectid);
            return;
        }

        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });
        requests::set_proxy($proxy);
        if (!empty($this->subjectid)) {
            $html = requests::get('https://book.douban.com/subject/' . $this->subjectid .'/');
            if (!empty($html)) {
                $dd_new_price = selector::select($html, "//span[contains(text(),'当当网')]/parent::*/following-sibling::*/span");
                $jd_new_price = selector::select($html, "//span[contains(text(),'京东商城')]/parent::*/following-sibling::*/span");
                $amz_new_price = selector::select($html, "//span[contains(text(),'亚马逊')]/parent::*/following-sibling::*/span");
                $bc_new_price = selector::select($html, "//span[contains(text(),'中国图书网')]/parent::*/following-sibling::*/span");
                $dzy_price = selector::select($html, "//span[contains(text(),'多抓鱼')]/parent::*/following-sibling::*/span");


                $douban_es_count = selector::select($html, "//a[@href=\"https://book.douban.com/subject/" . $this->subjectid . "/offers\"]");
                $douban_es_low_high = selector::select($html, "//a[@href=\"https://book.douban.com/subject/" . $this->subjectid . "/offers\"]//following-sibling::*");
                $douban_es_want = selector::select($html, "//a[contains(text(),'人想读')]");

                // 获取当当新书的售卖价格
                Log::info('$dd_new_price = '. json_encode($dd_new_price));
                if (is_array($dd_new_price)) {
                    $dd_new_price = $dd_new_price[0];
                }
                $dd_new_price = str_replace(array("\r\n", "\n", "\r", " ","元","起"), '', $dd_new_price);
                if (is_null($dd_new_price) || empty($dd_new_price)) {
                    $dd_new_price = 0;
                }

                // 获取京东商城新书的售卖价格
                Log::info('$jd_new_price = '. json_encode($jd_new_price));
                if (is_array($jd_new_price)) {
                    $jd_new_price = $jd_new_price[0];
                }
                $jd_new_price = str_replace(array("\r\n", "\n", "\r", " ","元","起"), '', $jd_new_price);
                if (is_null($jd_new_price) || empty($jd_new_price)) {
                    $jd_new_price = 0;
                }

                // 获取亚马逊新书的售卖价格
                Log::info('$amz_new_price = '. json_encode($amz_new_price));
                if (is_array($amz_new_price)) {
                    $amz_new_price = $amz_new_price[0];
                }
                $amz_new_price = str_replace(array("\r\n", "\n", "\r", " ","元","起"), '', $amz_new_price);
                if (is_null($amz_new_price) || empty($amz_new_price)) {
                    $amz_new_price = 0;
                }

                // 获取中国图书网新书的售卖价格
                Log::info('$bc_new_price = '. json_encode($bc_new_price));
                if (is_array($bc_new_price)) {
                    $bc_new_price = $bc_new_price[0];
                }
                $bc_new_price = str_replace(array("\r\n", "\n", "\r", " ","元","起"), '', $bc_new_price);
                if (is_null($bc_new_price) || empty($bc_new_price)) {
                    $bc_new_price = 0;
                }

                // 获取多抓鱼二手书的价格
                Log::info('$dzy_price = '. json_encode($dzy_price));
                if (is_array($dzy_price)) {
                    $dzy_price = $dzy_price[0];
                }
                $dzy_price = str_replace(array("\r\n", "\n", "\r", " ","元","起"), '', $dzy_price);
                if (is_null($dzy_price) || empty($dzy_price)) {
                    $dzy_price = 0;
                }

                // 获取二手市场上售卖的本书
                Log::info('$douban_es_count = '. json_encode($douban_es_count));
                $ben = mb_strpos($douban_es_count, '本');
                $douban_es_count = mb_substr($douban_es_count, 0, $ben);
                if (is_null($douban_es_count) || empty($douban_es_count)) {
                    $douban_es_count = 0;
                }

                // 获取二手市场上的最低价和最高价
                Log::info('$douban_es_low_high = ' . json_encode($douban_es_low_high));
                $douban_es_low_high = str_replace(array("\r\n", "\n", "\r", " ", "(", "元)", "元以上)"), '', $douban_es_low_high);
                $douban_es_low_high = explode('至', $douban_es_low_high);
                $douban_es_low = $douban_es_low_high[0];
                if (is_null($douban_es_low) || empty($douban_es_low)) {
                    $douban_es_low = 0;
                }
                $douban_es_high = 0;
                if (count($douban_es_low_high)>1){
                    $douban_es_high = $douban_es_low_high[1];
                    if (is_null($douban_es_high) || empty($douban_es_high)) {
                        $douban_es_high = 0;
                    }
                }

                // 获取二手市场上的需求人数
                Log::info('$douban_es_want = '. json_encode($douban_es_want));
                if (is_array($douban_es_want)) {
                    $douban_es_want = $douban_es_want[1];
                }
                $douban_es_want = str_replace(array("\r\n", "\n", "\r", ' '), '', $douban_es_want);
                $ren = mb_strpos($douban_es_want, '人');
                $douban_es_want = mb_substr($douban_es_want, 0, $ren);
                if (is_null($douban_es_want) || empty($douban_es_want)) {
                    $douban_es_want = 0;
                }

                Log::info('$dd_new_price = ' . $dd_new_price);
                Log::info('$jd_new_price = ' . $jd_new_price);
                Log::info('$amz_new_price = ' . $amz_new_price);
                Log::info('$bc_new_price = ' . $bc_new_price);
                Log::info('$dzy_price = ' . $dzy_price);
                Log::info('$douban_es_count = ' . $douban_es_count);
                Log::info('$douban_es_low_high = ' . json_encode($douban_es_low_high));
                Log::info('$douban_es_low = ' . $douban_es_low);
                if (count($douban_es_low_high)>1) {
                    Log::info('$douban_es_high = ' . $douban_es_high);
                }
                Log::info('$douban_es_want = ' . $douban_es_want);
                $book = Book::where('subjectid', $this->subjectid)->first();
                $book_prices = BookPrice::where('book_id', $book->id)->orderByDesc('id')->first();
                if (!$book_prices) {
                    DB::insert('insert into book_prices (book_id, isbn, dd_new_price, jd_new_price, amz_new_price, bc_new_price, dzy_price, douban_es_count, douban_es_low, douban_es_high, douban_es_want_count, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$book->id, $book->isbn, $dd_new_price, $jd_new_price, $amz_new_price, $bc_new_price, $dzy_price, $douban_es_count, $douban_es_low, $douban_es_high, $douban_es_want, Carbon::now(), Carbon::now()]);
                }else{
                    DB::update('update book_prices set dzy_price=?, updated_at=? where book_id=?', [
                        $dzy_price, now(), $book->id
                    ]);
                }
                $book_prices_count = BookPrice::where('isbn', $book->isbn)->count();
                $book->other_prices = $book_prices_count;
                $book->save();
            }else{
                Log::info('GetDbSecondDataJob '.$this->subjectid.' GET NOTHING '.$this->times);
                GetDbSecondDataJob::dispatch($this->subjectid, $this->times++)->delay(now()->addSecond(3));
            }
        }
    }
}
