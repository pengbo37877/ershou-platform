<?php

namespace App\Jobs;

use App\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use phpspider\core\requests;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class DownloadCoverImage implements ShouldQueue
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

    public function handle()
    {
        $book = Book::whereNull('cover_replace')->orderBy('id')->first();
        if (!$book) return;
        $disk = QiniuStorage::disk('qiniu');
        $file_name = Uuid::uuid4();
        while ($disk->exists($file_name)) {
            $file_name = Uuid::uuid4();
        }
        $file = $this->fetchData($book->cover_image);
        while(empty($file)){
            $file = $this->fetchData($book->cover_image);
        }
        if (!empty($file)) {
            $disk->put($file_name, $file);
            $book->cover_replace = $book->cover_replace = "http://pic.ovoooo.com/".$file_name;
            $book->save();
        }
    }

    function fetchData($url) {
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });
        $user_agent = 'Mozilla/4.0';
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
