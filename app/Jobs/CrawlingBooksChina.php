<?php

namespace App\Jobs;

use App\BooksChina;
use App\BooksChinaStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use phpspider\core\selector;

class CrawlingBooksChina implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->id = BooksChinaStatus::first()->current_id;
        Log::info('books_china_id='.$this->id);
        BooksChinaStatus::first()->update([
            'current_id' => $this->id+1
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });
        requests::set_proxy($proxy);
        $html = requests::get('http://www.bookschina.com/'.$this->id.'.htm');
        $name = selector::select($html, "//div[@class='padLeft10']/h1");
        if (!empty($name) && is_array($name)) {
            $name = $name[0];
        }
        $cover_image = selector::select($html, "//div[@class='coverImg']/img/@src");
        if (!empty($cover_image) && is_array($cover_image)) {
            $cover_image = $cover_image[0];
        }
        $author = selector::select($html, "//div[@class='author']/a");
        if (!empty($author) && is_array($author)) {
            $author = $author[0];
        }
        $press = selector::select($html, "//div[@class='publisher']/a");
        if (empty($press)) {
            $press = selector::select($html, "//div[@class='publisher']/em");
        }
        if (!empty($press) && is_array($press)) {
            $press = $press[0];
        }
        $series = selector::select($html, "//div[@class='series']/a");
        if (!empty($series) && is_array($series)) {
            $series = $series[0];
        }
        $publish_year = selector::select($html, "//div[@class='publisher']/i");
        if (!empty($publish_year) && is_array($publish_year)) {
            $publish_year = $publish_year[0];
        }
        $size = selector::select($html, "//div[@class='otherInfor']/em");
        if (!empty($size) && is_array($size)) {
            $size = $size[0];
        }
        $page_num = selector::select($html, "//div[@class='otherInfor']/i");
        if (!empty($page_num) && is_array($page_num)) {
            $page_num = $page_num[0];
        }
        $price = selector::select($html, "//div[@class='priceWrap']/del[@class='price']");
        if (!empty($price) && is_array($price)) {
            $price = $price[0];
        }
        $china_price = selector::select($html, "//div[@class='priceWrap']/span[@class='sellPrice']");
        if (!empty($china_price) && is_array($china_price)) {
            $china_price = $china_price[0];
        }
        $isbn = selector::select($html, "//div[@id='copyrightInfor']/ul/li[2]");
        if (!empty($isbn) && is_array($isbn)) {
            $isbn = mb_substr($isbn[0],4,13);
        }
        $binding = selector::select($html, "//div[@id='copyrightInfor']/ul/li[3]");
        if (!empty($binding) && is_array($binding)) {
            $binding = mb_substr($binding,3,strlen($binding)-1);
        }
        $weight = selector::select($html, "//div[@id='copyrightInfor']/ul/li[6]");
        if (!empty($weight) && is_array($weight)) {
            $weight = mb_substr($weight,3,strlen($weight)-1);
        }
        $summary = selector::select($html, "//div[@id='brief']/p");
        if (!empty($summary) && is_array($summary)) {
            $summary = join('', $summary);
        }
        $author_intro = selector::select($html, "//div[@id='zuozhejianjie']/p");
        if (!empty($author_intro) && is_array($author_intro)) {
            $author_intro = join('', $author_intro);
        }
        $catalog = selector::select($html, "//div[@id='catalog']/div");
        if (!empty($catalog) && is_array($catalog)) {
            $catalog = join('', $catalog);
        }
        $recommendation = selector::select($html, "//p[@class='recomand']");
        if (!empty($recommendation) && is_array($recommendation)) {
            $recommendation = $recommendation[0];
        }
        $category = selector::select($html, "//li[@class='kind']/div/a");
        if (!empty($category) && is_array($category)) {
            $category = join(',', $category);
        }
        $rating_num = selector::select($html, "//div[@class='startWrap']/em");
        if (!empty($rating_num) && is_array($rating_num)) {
            $rating_num = $rating_num[0];
        }
        $num_raters = selector::select($html, "//div[@class='startWrap']/a");
        if (!empty($num_raters) && is_array($num_raters)) {
            $num_raters = $num_raters[0];
        }
        if (empty($isbn) || strlen($isbn)!=13) return;
        $data = [
            'books_china_id' => $this->id,
            'name' => $name,
            'cover_image' => $cover_image,
            'author' => $author,
            'press' => $press,
            'series' => $series,
            'publish_year' => $publish_year,
            'size' => $size,
            'page_num' => $page_num,
            'price' => preg_replace('/￥/i', '', $price),
            'china_price' => preg_replace('/￥/i', '', $china_price),
            'isbn' => $isbn,
            'binding' => $binding,
            'weight' => $weight,
            'summary' => $summary,
            'author_intro' => $author_intro,
            'catalog' => $catalog,
            'recommendation' => $recommendation,
            'category' => $category,
            'rating_num' => $rating_num,
            'num_raters' => $num_raters
        ];

        BooksChina::create($data);
    }
}
