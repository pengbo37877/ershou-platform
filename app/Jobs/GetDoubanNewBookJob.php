<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use phpspider\core\selector;

class GetDoubanNewBookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = 'https://book.douban.com/latest?icn=index-latestbook-all';
        $data = $this->fetchData($url);
        if (count($data)>0) {
            $this->createBooks($data);
        }
    }

    /**
     * @param array $ids
     */
    function createBooks($ids) {
        collect($ids)->each(function ($id) {
            CrawlingByWebPageSubjectId::dispatch($id)->delay(now()->addSecond(rand(0, 300)));
        });
    }

    function fetchData($url) {
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });
        requests::set_proxy($proxy);
        $html = requests::get($url);
        if (!empty($html)) {
            $urls = selector::select($html, "//h2/a/@href");
            if (is_array($urls)){
                $urls = array_map(function($u){
                    return $this->findNum($u);
                }, $urls);
                Log::info('new book ids='.json_encode($urls));
                return $urls;
            }
            return [];
        }else{
            return [];
        }
    }

    function findNum($str=''){
        $str=trim($str);
        if(empty($str)){return '';}
        $temp=array('1','2','3','4','5','6','7','8','9','0');
        $result='';
        for($i=0;$i<strlen($str);$i++){
            if(in_array($str[$i],$temp)){
                $result.=$str[$i];
            }
        }
        return $result;
    }
}
