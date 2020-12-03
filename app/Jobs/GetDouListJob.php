<?php

namespace App\Jobs;

use App\DouList;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use phpspider\core\selector;

class GetDouListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $doulist_id;
    protected $times; // 最多重试3次

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($doulist_id, $times = 1)
    {
        $this->doulist_id = $doulist_id;
        $this->times = $times;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (is_null($this->doulist_id) || empty($this->doulist_id)) {
            return;
        }
        if ($this->times >= 3){
            return;
        }
        $list = DouList::where('doulist_id', $this->doulist_id)->first();
        if ($list) {
            $book_count = $list->book_count;
            $start = $list->start;
            if ($start<$book_count) {
                $url = 'https://www.douban.com/doulist/'.$this->doulist_id.'/?start='.$start.'&sub_type=4';
                $data = $this->fetchData($url);
                if ($data) {
                    $this->updateDoulist($data, $list);
                }
            }
        }else{
            $url = 'https://www.douban.com/doulist/'.$this->doulist_id.'/?sub_type=4';
            $data = $this->fetchData($url);
            if ($data) {
                $this->createDoulist($data);
            }
        }
    }

    function fetchData($url) {
        Log::info('GET Doulist url='.$url);
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });
        requests::set_proxy($proxy);
        $html = requests::get($url);
        if (!empty($html)) {
            $book_count = selector::select($html, "//div[@class=\"doulist-filter\"]/a[contains(text(),'图书')]/span");
            $book_count = str_replace(array("(", ")", "\r", " ",), '', $book_count);
            $name = selector::select($html, "//div[@id=\"content\"]/h1");
            $desc = selector::select($html, "//*[@id=\"link-report\"]/div[1]");
            $following_count = selector::select($html, "//a[@class=\"doulist-followers-link\"]");
            $recommend_count = selector::select($html, "//span[@class=\"rec-num\"]");
            $recommend_count = str_replace(array("人", "\r", " ",), '', $recommend_count);
            $urls = selector::select($html, "//div[@class=\"title\"]/a/@href");
            Log::info('book_count='.$book_count);
            Log::info('name='.$name);
            Log::info('desc='.$desc);
            Log::info('following_count='.$following_count);
            Log::info('recommend_count='.$recommend_count);
            Log::info('urls='.json_encode($urls));
            if (is_array($urls)){
                $urls = array_map(function($u){
                    return $this->findNum($u);
                }, $urls);
            }
            $data = [
                'book_count' => $book_count,
                'name' => $name,
                'desc' => $desc,
                'following_count' => $following_count,
                'recommend_count' => $recommend_count,
                'subjectids' => empty($urls)?'':join(',', $urls)
            ];
            Log::info('GET Doulist:\n'.json_encode($data));
            if ($book_count && intval($book_count)>0) {
                return $data;
            }
            return null;
        }else{
            Log::info('GET Doulist '.$this->doulist_id.' NOTHING');
            GetDouListJob::dispatch($this->doulist_id, $this->times++)->delay(now()->addSecond(3));
        }
    }

    function createDoulist($data) {
        $data['doulist_id'] = $this->doulist_id;
        $data['start'] = 25;
        DouList::insert($data);
        if (intval($data['book_count'])>25){
            GetDouListJob::dispatch($this->doulist_id)->delay(now()->addSecond(3));
        }
    }

    function updateDoulist($data, DouList $list) {
        $list->subjectids = $list->subjectids.','.$data['subjectids'];
        $list->start = intval($list->start)+25;
        $list->book_count = $data['book_count'];
        $list->save();
        if (intval($data['book_count'])>$list->start){
            GetDouListJob::dispatch($this->doulist_id)->delay(now()->addSecond(3));
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
