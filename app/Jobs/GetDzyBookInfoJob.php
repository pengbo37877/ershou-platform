<?php

namespace App\Jobs;

use App\Book;
use App\BookPrice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;

class GetDzyBookInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $name;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = 'https://www.duozhuayu.com/api/search?q='.$this->name.'&type=normal';
        $data = $this->get_contents($url);
        $json = json_decode($data, true);
        $books = $json->data;
        collect($books)->each(function($book){
            if ($book->type=='book') {
                $bp = BookPrice::where('isbn', $book->book->isbn13)->get();
                if (!$bp) {
                    BookPrice::create([
                        'isbn' => $book->book->isbn13,
                        'dzy_price' => floatval($book->book->price)/100,
                        'dzy_new_price' => floatval($book->book->newConditionPrice)/100
                    ]);
                }
            }
        });
    }

    public function get_contents($url, $name)
    {
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
        });
        requests::set_proxy($proxy);
        requests::set_header('x-api-version', '0.0.4');
        requests::set_header('x-refer-request-id', '53863513893898420-1544336772935-13739');
        requests::set_header('x-request-id', '53863513893898420-1544336780793-97673');
        requests::set_header('x-request-misc', '{"platform":"browser"}');
        requests::set_header('x-request-page', '/search/'.urlencode($name));
        requests::set_header('x-request-prev-page', '/search');
        requests::set_header('x-request-token', '6461338fe50ce8d685abe6c28d66e707095e3b89d1bcedde45716fa53eb925360a0865be4b14016d');
        requests::set_header('x-security-key', '99766270');
        requests::set_header('x-timestamp', '1544336780794');
        requests::set_header('x-user-id', '53863513893898420');
        $json_str = requests::get($url);
        Log::info("dzy info:".$json_str);
        return $json_str;
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
