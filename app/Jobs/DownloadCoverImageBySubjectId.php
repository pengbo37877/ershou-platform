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
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class DownloadCoverImageBySubjectId implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subjectid;

    /**
     * Create a new job instance.
     *
     * @param $subjectid
     */
    public function __construct($subjectid)
    {
        $this->subjectid = $subjectid;
    }

    public function handle()
    {
        $book = Book::where('subjectid', $this->subjectid)->first();
        if (!$book) return;
        if (strpos($book->cover_image, '.gif')) {
            return;
        }
        $disk = QiniuStorage::disk('qiniu');
        $file_name = Uuid::uuid4();
        while ($disk->exists($file_name)) {
            $file_name = Uuid::uuid4();
        }
        $file = $this->fetchData($book->cover_image);
        while (empty($file)) {
            $file = $this->fetchData($book->cover_image);
        }
        if (!empty($file)) {
            $disk->put($file_name, $file);
            $book->cover_replace = "http://pic.ovoooo.com/" . $file_name;
            $book->save();
            Log::info('DownloadCoverImageBySubjectId cover=' . $book->cover_replace);
        }
    }

    function fetchData($url)
    {
        //        $proxy = Cache::remember('data5u_proxy', 1, function () {
        //            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=95c9de7cf0a421965125162a002b5637&sep=3');
        //        });
        $proxy = Cache::remember('data5u_proxy2', 1, function () {
//            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
        });
        $user_agent = 'Mozilla/4.0';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $file_contents = curl_exec($ch);
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
