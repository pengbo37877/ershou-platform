<?php

namespace App\Jobs;

use App\Book;
use App\PendingBook;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class FetchBookFromDouban implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pendingBook;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(PendingBook $pendingBook)
    {
        $this->pendingBook = $pendingBook;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->fetchBookByIsbn($this->pendingBook->isbn);
    }

    public function fetchBookByIsbn($isbn)
    {
        $url = 'https://api.douban.com/v2/book/isbn/' . $isbn;
        return $this->curl_string($url, $isbn);
    }

    public function buildBook($file_contents, $isbn)
    {
        if ($file_contents) {
            $book_json = json_decode($file_contents, true);
            if (isset($book_json['code']) && $book_json['code'] == 6000) {
                return null;
            }
            $book = Book::where('isbn', $isbn)->first();
            if (!$book) {
                $disk = QiniuStorage::disk('qiniu');
                $file_name = Uuid::uuid4()->toString();
                while ($disk->exists($file_name)) {
                    $file_name = Uuid::uuid4()->toString();
                }
                $disk->put($file_name, $this->get_image_url(isset($book_json['image']) ? $book_json['image'] : ''));
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
                    'cover_replace' => 'http://pic.ovoooo.com/'.$file_name,
                ]);
            } else {
                PendingBook::where('isbn', $isbn)->delete();
                return null;
            }
            // 删除这个isbn的书
            PendingBook::where('isbn', $isbn)->delete();
//                Log::info('新加的书是《'.$book->name.'》isbn='.$book->isbn);
            // 豆瓣评分6.0以上的才收
            if (is_numeric($book->rating_num) && intval($book->rating_num) >= 6.0) {
                $book->update([
                    'can_recover' => true
                ]);
            }
            // 按标签过滤来决定收不收
            $category = $book->category;
            if (strstr($category, '灵修') ||
                strstr($category, '郭敬明') ||
                strstr($category, '沧月') ||
                strstr($category, '张小娴') ||
                strstr($category, '席娟') ||
                strstr($category, '亦舒') ||
                strstr($category, '教材') ||
                strstr($category, '教辅') ||
                strstr($category, '穿越') ||
                strstr($category, '佛教') ||
                strstr($category, '佛学') ||
                strstr($category, '言情') ||
                strstr($category, '耽美') ||
                strstr($category, '考古') ||
                strstr($category, '养生') ||
                strstr($category, '手工') ||
                strstr($category, '摄影') ||
                strstr($category, '工具书') ||
                strstr($category, '计算机') ||
                strstr($category, '神经网络') ||
                strstr($category, '算法') ||
                strstr($category, '程序') ||
                strstr($category, 'web') ||
                strstr($category, 'UCD') ||
                strstr($category, '通信') ||
                strstr($category, '校园') ||
                strstr($category, '落落') ||
                strstr($category, '几米') ||
                strstr($category, '漫画') ||
                strstr($category, '绘本') ||
                strstr($category, '自助游') ||
                strstr($category, '健康') ||
                strstr($category, '家居') ||
                strstr($category, '股票') ||
                strstr($category, '理财') ||
                strstr($category, '策划') ||
                strstr($category, '职场') ||
                strstr($category, '教育') ||
                strstr($category, '情感') ||
                strstr($category, '鸡汤') ||
                strstr($category, '人际关系') ||
                strstr($category, '军事') ||
                strstr($category, '宗教') ||
                strstr($category, '音乐') ||
                strstr($category, '两性') ||
                strstr($category, '科技') ||
                strstr($category, '投资') ||
                strstr($category, '广告') ||
                strstr($category, '金融') ||
                strstr($category, '营销') ||
                strstr($category, '企业史') ||
                strstr($category, '创业') ||
                strstr($category, '数学') ||
                strstr($category, '安妮宝贝') ||
                strstr($category, '庆山') ||
                strstr($category, '网络小说') ||
                strstr($category, '晋江')) {
                $book->update([
                    'can_recover' => false
                ]);
            }
        }
        return null;
    }

    function curl_string ($url, $isbn)
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
        Log::info($body);
        return $this->buildBook($body, $isbn);
    }

    public function get_image_url($url)
    {
        $user_agent = 'Mozilla/4.0';
        $proxy = file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
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
