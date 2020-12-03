<?php

namespace App\Jobs;

use App\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class GetNewBookJob implements ShouldQueue
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
        $not_exist_isbn = DB::connection('xsc')->select("select * from isbn_finish_not_exist where isbn like '9787%' and status=3 limit 1")[0];
        $book = Book::where('isbn', $not_exist_isbn->isbn)->first();
        if (!$book) {
            $book = $this->fetchBookByIsbn($not_exist_isbn->isbn);
            if ($book) {
                DB::connection('xsc')->delete("delete from isbn_finish_not_exist where isbn=?",[$not_exist_isbn->isbn]);
            }else{
                DB::connection('xsc')->update("update isbn_finish_not_exist set status=4 where isbn=?",[$not_exist_isbn->isbn]);
            }
        }
    }

    public function fetchBookByIsbn($isbn)
    {
        $url = 'https://api.douban.com/v2/book/isbn/' . $isbn;
        $bodyStr = $this->curl_string($url);
        return $this->buildBook($bodyStr, $isbn);
    }

    public function buildBook($file_contents, $isbn)
    {
        if ($file_contents) {
            $book_json = json_decode($file_contents, true);
            if (isset($book_json['code']) && $book_json['code'] == 6000) {
                GetNewBookJob::dispatchNow();
                return;
            }
            $book = Book::where('isbn', $isbn)->first();
            if (!$book) {
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
                    'price' => isset($book_json['price']) ? $this->buildPrice($book_json['price']) : '',
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
                    'user_add' => 200
                ]);
                return $book;
            }
            return $book;
        }
        return null;
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

    function curl_string ($url)
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
        if ($code == '404') {
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
