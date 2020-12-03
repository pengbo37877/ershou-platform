<?php

namespace App\Jobs;

use App\Series;
use App\Utils\Tools;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpspider\core\requests;
use phpspider\core\selector;

class CrawlingAuthorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $aid;

    /**
     * Create a new job instance.
     *
     * @param $sid
     * @param int $page
     */
    public function __construct($aid)
    {
        $this->aid = $aid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (is_null($this->aid) || empty($this->aid)) {
            return;
        }

        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });
        requests::set_proxy($proxy);
        if (!empty($this->sid)) {
            $html = requests::get('https://book.douban.com/author/' . $this->aid.'/');
            if (!empty($html)) {
                $data = [];
                $data['author_id'] = $this->aid;
                $name = selector::select($html, "//h1");
                if (is_array($name)) {
                    $name = $name[0];
                }
                $data['name'] = $name;

                $avatar = selector::select($html, "//*[@class='pic']/*[@class='nbg']/@href");
                if (is_array($avatar)) {
                    $avatar = $avatar[0];
                }
                $data['avatar'] = $avatar;

                $info = selector::select($html, "//*[@class='info']/ul/li");
                if (is_array($info)) {
                    foreach ($info as $item) {
                        $item = str_replace(["\r\n", "\n", "\r", "\t", " "], "", strip_tags($item));
                        Log::info('author_data item='.$item);
                        $item_array = explode(':', $item);
                        if (count($item_array)==2) {
                            if ($item_array[0] == '性别') {
                                $data['gender'] = $item_array[1];
                            }else if($item_array[0] == '生卒日期') {
                                $data['live_day'] = $item_array[1];
                            }else if($item_array[0] == '国家/地区') {
                                $data['country'] = $item_array[1];
                            }else if($item_array[0] == '更多外文名') {
                                $data['en_name'] = $item_array[1];
                            }else if($item_array[0] == '更多中文名') {
                                $data['cn_name'] = $item_array[1];
                            }
                        }
                    }
                }

                $intro = selector::select($html, "//*[@id='intro']/*[@class='bd']");
                if (is_array($intro)) {
                    $intro = $intro[0];
                }
                $data['intro'] = $intro;

                $author = Author::where('author_id', $this->aid)->first();
                if (!$author) {
                    Author::create($data);
                }

                Log::info('author_data '.$this->aid.'=> data='.json_encode($data, JSON_UNESCAPED_UNICODE));
            }else{
                Log::info('CrawlingAuthorJob '.$this->sid.' get nothing.');
                CrawlingAuthorJob::dispatch($this->aid)->delay(now()->addSecond(3));
            }
        }
    }
}
