<?php

namespace App\Jobs;

use App\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class UpdateBookFromDouban implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $book;

    /**
     * Create a new job instance.
     *
     * @param Book $book
     */
    public function __construct(Book $book)
    {
        $this->book = $book;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = 'https://api.douban.com/v2/book/isbn/' . $this->book->isbn;
        $file_contents = $this->get_contents($url);
        if (is_null($file_contents) || empty($file_contents)) {
            // 没有拿到数据，再来一遍
            UpdateBookFromDouban::dispatch($this->book)->delay(now()->addSecond(10));
        } else {
            $book_json = json_decode($file_contents, true);
            // 豆瓣还没有这本书的数据，放过它吧。
            if (isset($book_json['code']) && intval($book_json['code'])==6000) {
                $this->book->user_add = 1038;
                $this->book->save();
                return;
            }
            if (!isset($book_json['title']) || empty($book_json['title'])) {
                return;
            }
            $this->book->update([
                'name' => isset($book_json['title']) ? $book_json['title'] : '',
                'author' => isset($book_json['author']) ? join(' ', $book_json['author']) : '',
                'press' => isset($book_json['publisher']) ? $book_json['publisher'] : '',
                'publish_year' => isset($book_json['pubdate']) ? $book_json['pubdate'] : '',
                'original_name' => isset($book_json['origin_title']) ? $book_json['origin_title'] : '',
                'subtitle' => isset($book_json['subtitle']) ? $book_json['subtitle'] : '',
                'translator' => isset($book_json['translator']) ? join(' ', $book_json['translator']) : '',
                'page_num' => isset($book_json['pages']) ? $book_json['pages'] : '',
                'binding' => isset($book_json['binding']) ? $book_json['binding'] : '',
                'series' => isset($book_json['series']) ? $book_json['series']['title'] : '',
                'cover_image' => isset($book_json['images']) ? $book_json['images']['large'] : '',
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
                'price' => $this->buildPrice($this->book, isset($book_json['price']) ? $book_json['price'] : ''),
                'user_add' => 1039
            ]);
        }
    }

    public function buildPrice(Book $book, $price)
    {
        // 价格如果正常就不更新
        if (!is_null($book->price) && is_numeric($book->price) && floatval($book->price)>0){
            return $book->price;
        }
        $price = preg_replace('/cny/i', '', $price);
        $price = preg_replace('/CNY/i', '', $price);
        $price = preg_replace('/元/i', '', $price);
        $price = preg_replace('/rmb/i', '', $price);
        $price = preg_replace('/RMB/i', '', $price);
        $price = preg_replace('/￥/i', '', $price);
        $price = preg_replace('/,/i', '', $price);
        $price = preg_replace('# #', '', $price);
        return $price;
    }

    public function get_contents($url)
    {
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });
        requests::set_proxy($proxy);
        $json_str = requests::get($url);
        return $json_str;
    }
}
