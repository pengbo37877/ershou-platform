<?php

namespace App\Console\Commands;

use App\Book;
use App\Jobs\CrawlingBook;
use App\UserSearchHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpspider\core\requests;
use phpspider\core\selector;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class GetUserSearchBook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:search {q}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get book info from douban by user search history';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Get User Search Books');
        $q = $this->argument('q');
        if ($q == 'random') {
            $histories = DB::select("select * from user_search_histories where start=0 or start<total order by id desc limit 30");
            $i=0;
            foreach ($histories as $history) {
//                $count = UserSearchHistory::where('start', '>', 0)->where('q', $history->q)->count();
//                if ($history->start==0 && $count>0) {
//                    Log::info($history->q.' 已经开始抓取了，跳过');
//                    DB::update("update user_search_histories set start=1,total=1 where q=? and start=0 and total=0",
//                        [$history->q]);
//                }else {
////                $this->updateHistory($histories[$i]);
//                    CrawlingBook::dispatch($history)->delay(now()->addSecond($i*5));
//                }
//                $i+=1;
                CrawlingBook::dispatch($history)->delay(now()->addSecond($i*10));
                $i+=1;
            }
        }else{
            Log::info('你觉得可以吗？');
        }
    }

    public function updateHistory($history)
    {
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
        });
        requests::set_proxy($proxy);
        $json_str = requests::get('https://api.douban.com/v2/book/search?q='.$history->q.'&start='.$history->start);
        if (!empty($json_str)) {
            $json = json_decode($json_str, true);
            $books = $json['books'];
            foreach ($books as $book_json) {
                $isbn13 = isset($book_json['isbn13'])?$book_json['isbn13']:'';
                $start_with_9787 = strpos($isbn13, '9787');
                $publish_year = isset($book_json['pubdate']) ? $book_json['pubdate'] : '';
                $price = isset($book_json['price']) ?  $this->buildPrice($book_json['price']): '';
                $img = isset($book_json['image'])? $book_json['image'] : '';
                $has_image = ($img != 'https://img1.doubanio.com/f/shire/5522dd1f5b742d1e1394a17f44d590646b63871d/pics/book-default-lpic.gif');
                $db_book = null;
                if ($has_image && !empty($isbn13) && $start_with_9787==0 && !empty($publish_year) && !empty($price)) {
                    $db_book = Book::where('isbn', $isbn13)->first();
                    if (!$db_book) {
                        $this->createBook($book_json, $isbn13);
                    }
                }
            }

            $user_history = UserSearchHistory::find($history->id);
            if (intval($json['total'])>=2000) {
                $user_history->update([
                    'start' => $json['total'],
                    'total' => $json['total']
                ]);
            }else {
                $user_history->update([
                    'start' => intval($json['start']) + intval($json['count']),
                    'total' => $json['total']
                ]);
            }
        }
    }

    public function buildPrice($price)
    {
        $price = preg_replace('/cny/i', '', $price);
        $price = preg_replace('/元/i', '', $price);
        $price = preg_replace('/rmb/i', '', $price);
        $price = preg_replace('/￥/i', '', $price);
        $price = preg_replace('/,/i', '', $price);
        $price = preg_replace('# #', '', $price);
        return $price;
    }

    public function createBook($book_json, $isbn)
    {
        $disk = QiniuStorage::disk('qiniu');
        $file_name = Uuid::uuid4();
        while ($disk->exists($file_name)) {
            $file_name = Uuid::uuid4();
        }
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
        });
        requests::set_proxy($proxy);
        $file = requests::get(isset($book_json['image']) ? $book_json['image'] : '');
        while (is_null($file)) {
            $file = requests::get(isset($book_json['image']) ? $book_json['image'] : '');
        }
        $disk->put($file_name, $file);
        $book = Book::create([
            'isbn' => $isbn,
            'name' => isset($book_json['title']) ? $book_json['title'] : '',
            'author' => isset($book_json['author']) ? join(' ', $book_json['author']) : '',
            'press' => isset($book_json['publisher']) ? $book_json['publisher'] : '',
            'publish_year' => isset($book_json['pubdate']) ? $book_json['pubdate'] : '',
            'original_name' => isset($book_json['origin_title']) ? $book_json['origin_title'] : '',
            'subtitle' => isset($book_json['subtitle']),
            'translator' => isset($book_json['translator']) ? join(' ', $book_json['translator']) : '',
            'page_num' => isset($book_json['pages']) ? $book_json['pages'] : '',
            'price' => isset($book_json['price']) ? preg_replace('/cny/i', '', preg_replace('/元/i', '', $book_json['price'])) : '',
            'binding' => isset($book_json['binding']) ? $book_json['binding'] : '',
            'series' => isset($book_json['series']) ? $book_json['series']['title'] : '',
            'cover_image' => isset($book_json['image']) ? $book_json['image'] : '',
            'rating_num' => isset($book_json['rating']) ? $book_json['rating']['average'] : '',
            'num_raters' => isset($book_json['rating']) ? $book_json['rating']['numRaters'] : '',
            'summary' => isset($book_json['summary']) ? $book_json['summary'] : '',
            'author_intro' => isset($book_json['author_intro']) ? $book_json['author_intro'] : '',
            'catalog' => isset($book_json['catalog']) ? preg_replace('/··/', '', preg_replace('/…/i', '', $book_json['catalog'])) : '',
            'category' => isset($book_json['tags']) ? join(',', array_map(function ($tag) {
                return $tag['name'];
            }, $book_json['tags'])) : '',
            'subjectid' => $book_json['id'],
            'publisher' => isset($book_json['publisher']) ? $book_json['publisher'] : '',
            'cover_replace' => $disk->downloadUrl($file_name),
        ]);
        return $book;
    }

    public function get_image_url($url)
    {
        $user_agent = 'Mozilla/4.0';
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
        });
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_PROXY, $proxy);
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt ($ch, CURLOPT_HEADER, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 120);
        $file_contents = curl_exec ($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code == 404) {
            return null;
        }
        $header = '';
        $body = '';
        if ($code == '200') {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($file_contents, 0, $headerSize);
            $body = substr($file_contents, $headerSize);
        }
        curl_close($ch);
        return $body;
    }
}
