<?php

namespace App\Listeners;

use App\Book;
use App\BookSku;
use App\CartItem;
use App\Events\GetDouBookInfo;
use App\PendingBook;
use App\SaleItem;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class GetDouBookInfoListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  GetDouBookInfo  $event
     * @return void
     */
    public function handle(GetDouBookInfo $event)
    {
        $book = $this->fetchBookByIsbn($event->isbn);
        if ($book) {
            SaleItem::create([
                'user_id' => $event->user_id,
                'book_id' => $book->id,
                'isbn' => $event->isbn,
                'can_recover' => $book->can_recover
            ]);
        }
    }

    public function fetchBookByIsbn($isbn)
    {
        $url = 'https://api.douban.com/v2/book/isbn/' . $isbn;
        $body = $this->curl_string($url);
        return $this->buildBook($body, $isbn);
    }

    public function buildBook($file_contents, $isbn)
    {
        $book = null;
        if ($file_contents) {
            $book_json = json_decode($file_contents, true);
            if (isset($book_json['code']) && $book_json['code'] == 6000) {
                return null;
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
                    'can_recover' => true,
                    'discount' => 18
                ]);
                return $book;
            }
        }
        return $book;
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
        Log::info($body);
        return $body;
    }
}
