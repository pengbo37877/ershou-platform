<?php

namespace App\Jobs;

use App\Book;
use App\BookPrice;
use App\Utils\Tools;
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

class CrawlingByWebPageSubjectId2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $subjectid;
    protected $times;

    /**
     * Create a new job instance.
     *
     * @param $subjectid
     * @param int $times
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

//        $proxy = Cache::remember('subject_proxy', 1, function () {
//            return file_get_contents('http://dev.kdlapi.com/api/getproxy/?orderid=955530156661450&num=1&protocol=1&method=2&an_tr=1&an_an=1&an_ha=1&sp1=1&quality=1&sort=2&dedup=1&sep=1');
//        });
        $proxy = Cache::remember('data5u_proxy2', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
        });
        requests::set_proxy($proxy);
        if (!empty($this->subjectid)) {
            $html = requests::get('https://book.douban.com/subject/' . $this->subjectid .'/');
            if (!empty($html)) {
                $data = [];
                $data['subjectid'] = $this->subjectid;
                $all_intro = selector::select($html, "//*[@class='intro']");
                $summary1 = selector::select($html, "//*[@id=\"link-report\"]/div/div");
                $summary2 = selector::select($html, "//span[contains(@class, \"all\") and contains(@class, 'hidden')]/div/div[@class=\"intro\"]");
                if (is_array($summary1)) {
                    $summary1 = strip_tags($summary1[0]);
                }else{
                    $summary1 = strip_tags($summary1);
                }
                if (is_array($summary2)) {
                    $summary2 = strip_tags($summary2[0]);
                }else{
                    $summary2 = strip_tags($summary2);
                }
                $summary = empty($summary1)?$summary2:$summary1;

                $data['summary'] = $summary;

                $author_intro1 = selector::select($html, "//*[@id=\"content\"]/div/div[1]/div[3]/div[2]/div/div");
                $author_intro2 = selector::select($html, "//*[@id=\"content\"]/div/div[1]/div[3]/div[3]/div/div");

                if (is_array($author_intro1)) {
                    $author_intro1 = strip_tags($author_intro1[0]);
                }else{
                    $author_intro1 = strip_tags($author_intro1);
                }

                if (is_array($author_intro2)) {
                    $author_intro2 = strip_tags($author_intro2[0]);
                }else{
                    $author_intro2 = strip_tags($author_intro2);
                }

                $author_intro = empty($author_intro1)?$author_intro2:$author_intro1;

                $data['author_intro'] = $author_intro;

                // 内容补救
                if (empty($author_intro)) {
                    if (!empty($summary2) && count($all_intro) == 3) {
                        $author_intro = strip_tags($all_intro[2]);
                        $data['author_intro'] = $author_intro;
                    }else if(!empty($summary2) && count($all_intro) == 4) {
                        $author_intro = strip_tags($all_intro[3]);
                        $data['author_intro'] = $author_intro;
                    }
                }

                if (is_array($all_intro) && count($all_intro) == 2 && (empty($summary) || empty($author_intro))) {
                    if (empty($summary)) {
                        $summary = strip_tags($all_intro[0]);
                        $data['summary'] = $summary;
                    }
                    if (empty($author_intro)) {
                        $author_intro = strip_tags($all_intro[1]);
                        $data['author_intro'] = $author_intro;
                    }
                }

                $catalog = strip_tags(selector::select($html, "//*[contains(@id, 'dir') and contains(@id,'full')]"));
                $catalog = preg_replace("/(收起)/i", "", $catalog);
                $catalog = str_replace(['(', ')', '· · · · · ·'], '', $catalog);

                $data['catalog'] = $catalog;

                $name = selector::select($html, "//*[@id=\"wrapper\"]/h1/span");

                $data['name'] = $name;

                $image = selector::select($html, "//*[@id=\"mainpic\"]/a/img/@src");

                $data['cover_image'] = $image;

                $rating_num = selector::select($html, "//*[contains(@class,'ll') and contains(@class,'rating_num')]");
                $rating_num = empty($rating_num)?0:number_format(floatval($rating_num), 1);

                $data['rating_num'] = $rating_num;

                $num_raters = selector::select($html, "//*[@class=\"rating_people\"]/span");
                $num_raters = empty($num_raters)?0:intval($num_raters);

                $data['num_raters'] = $num_raters;

                $category = selector::select($html, "//*[@id=\"db-tags-section\"]/div/span");
                if (is_array($category)) {
                    $category = array_map(function($item) {
                        $c = preg_replace('/[(\xc2\xa0)|\s]+/', '', strip_tags($item));
                        return str_replace(["\r\n", "\n", "\r", "\t", " "], "", $c);
                    }, $category);
                    $data['category']=join(',', $category);
                }else{
                    $c = preg_replace('/[(\xc2\xa0)|\s]+/', '', strip_tags($category));
                    $category = str_replace(["\r\n", "\n", "\r", "\t", " "], "", $c);
                    $data['category']=$category;
                }

                $info = selector::select($html, "//*[@id=\"info\"]");
                if (is_array($info)) {
                    $info = $info[0];
                }
                $info = str_replace(["\n"], '', $info);
                $info_array = [];
                if (!empty($info)) {
                    $info_array = explode("<br/>", $info);
                    $info_array = array_map(function($item){
                        return str_replace([" "], '', strip_tags($item));
                    }, $info_array);
                }
                foreach($info_array as $item){
                    $item_array = explode(':', $item);
                    if ($item_array[0] == '作者') {
                        $data['author'] = $item_array[1];
                    }else if($item_array[0] == '副标题') {
                        $data['subtitle'] = $item_array[1];
                    }else if($item_array[0] == '出版社') {
                        $data['press'] = $item_array[1];
                    }else if($item_array[0] == '出版年') {
                        $publish_year = $item_array[1];
                        if (!empty($publish_year)) {
                            $publish_year = str_replace(['年','月','日'], '-', $publish_year);
                            $publish_year = trim($publish_year, '-');
                        }
                        $data['publish_year'] = $publish_year;
                    }else if($item_array[0] == '页数') {
                        $data['page_num'] = Tools::findNum($item_array[1]);
                    }else if($item_array[0] == '定价') {
                        // covert price to rmb
                        $price = preg_replace('/RMB/i', '', $item_array[1]);
                        $price = preg_replace('/rmb/i', '', $price);
                        $price = preg_replace('/cny/i', '', $price);
                        $price = preg_replace('/CNY/i', '', $price);
                        $price = preg_replace('/元/i', '', $price);
                        $data['price'] = $price;
                        $data['original_price'] = $price;
                    }else if($item_array[0] == '装帧') {
                        $data['binding'] = $item_array[1];
                    }else if($item_array[0] == 'ISBN') {
                        $data['isbn'] = trim(str_replace('-','', $item_array[1]));
                    }else if($item_array[0] == '出品方') {
                        $data['publisher'] = $item_array[1];
                    }else if($item_array[0] == '系列') {
                        $data['series'] = $item_array[1];
                    }else if($item_array[0] == '译者') {
                        $data['translator'] = $item_array[1];
                    }else if($item_array[0] == '原作名') {
                        $data['original_name'] = $item_array[1];
                    }
                };

                if (isset($data['isbn'])) {
                    $book = $this->createOrUpdateBook($data);
                    $this->fetchSecondHandInfo($html, $book);
                    $this->fetchRelation($html, $book);
                }

                Log::info($this->subjectid.'=> info_array='.json_encode($info_array, JSON_UNESCAPED_UNICODE));
                Log::info($this->subjectid.'=> data='.json_encode($data, JSON_UNESCAPED_UNICODE));


            }else{
                Log::info('CrawlingByWebPageSubjectId '.$this->subjectid.' get nothing.');
                CrawlingByWebPageSubjectId::dispatch($this->subjectid, $this->times++)->delay(now()->addSecond(3));
            }
        }
    }

    function createOrUpdateBook($data) {
        $book = Book::where('isbn', $data['isbn'])->first();
        if ($book) {
            $update_data = [
                'subtitle' => isset($data['subtitle'])?$data['subtitle']:'',
                'author' => isset($data['author'])?$data['author']:'',
                'translator' => isset($data['translator'])?$data['translator']:'',
                'press' => isset($data['press'])?$data['press']:'',
                'original_name' => isset($data['original_name'])?$data['original_name']:'',
                'rating_num' => $data['rating_num'],
                'num_raters' => $data['num_raters'],
                'publisher' => isset($data['publisher'])?$data['publisher']:'',
                'original_price' => isset($data['original_price'])?$data['original_price']:'',
                'cover_image' => $data['cover_image']
            ];
            if (empty($book->author_intro) && !empty($data['author_intro'])) {
                $update_data['author_intro'] = $data['author_intro'];
            }
            if (empty($book->catalog) && !empty($data['catalog'])) {
                $update_data['catalog'] = $data['catalog'];
            }
            if (empty($book->category) && !empty($data['category'])) {
                $update_data['category'] = $data['category'];
            }
            if (empty($book->summary) && !empty($data['summary'])) {
                $update_data['summary'] = $data['summary'];
            }
            $book->update($update_data);
//            if (empty($book->cover_replace)) {
//                DownloadCoverImageBySubjectId::dispatchNow($book->subjectid);
//            }
        }else{
            $book = Book::create($data);
//            if (empty($book->cover_replace)) {
//                DownloadCoverImageBySubjectId::dispatchNow($book->subjectid);
//            }
        }
        return $book;
    }

    function fetchSecondHandInfo($html, Book $book) {
        Log::info('fetchSecondHandInfo subjectid2='.$book->subjectid);
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
            $bookPrice = BookPrice::where('book_id', $book->id)->orderByDesc('id')->first();
            if (!$bookPrice) {
                DB::insert('insert into book_prices (book_id, isbn, dd_new_price, jd_new_price, amz_new_price, bc_new_price, dzy_price, douban_es_count, douban_es_low, douban_es_high, douban_es_want_count, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$book->id, $book->isbn, $dd_new_price, $jd_new_price, $amz_new_price, $bc_new_price, $dzy_price, $douban_es_count, $douban_es_low, $douban_es_high, $douban_es_want, Carbon::now(), Carbon::now()]);
            }else if (floatval($dzy_price)>0){
                DB::update('update book_prices set dd_new_price=?, jd_new_price=?, amz_new_price=?, bc_new_price=?, dzy_price=?, updated_at=? where id=?',
                    [$dd_new_price, $jd_new_price, $amz_new_price, $bc_new_price, $dzy_price, Carbon::now(), $bookPrice->id]);
            }
            $book->other_prices = BookPrice::where('isbn', $book->isbn)->count();
            $book->save();
        }
    }

    function fetchRelation($html, Book $book) {
        $data = selector::select($html, "//dd/a/@href");
        if (is_array($data)) {
            $hs = array_map(function ($href) {
               return Tools::findNum($href);
            }, $data);
            $c = DB::select('select count(*) as c from books_relation where subjectid = ?', [$book->subjectid])[0]->c;
            if ($c==0) {
                DB::insert('insert into books_relation (subjectid, subjectids) values (?, ?)', [$book->subjectid, join(',', $hs)]);
            }elseif ($c>0) {
                DB::update('update books_relation set subjectids = ? where subjectid = ?', [join(',', $hs), $book->subjectid]);
            }
            Log::info('fetchRelation subjectid='.$book->subjectid.' '.join(',', $hs));
        }
    }
}
