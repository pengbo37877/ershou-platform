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
use phpspider\core\requests;
use phpspider\core\selector;

class CrawlingSeriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $sid;

    /**
     * Create a new job instance.
     *
     * @param $sid
     * @param int $page
     */
    public function __construct($sid)
    {
        $this->sid = $sid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (is_null($this->sid) || empty($this->sid)) {
            return;
        }

        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
        });

        requests::set_proxy($proxy);
        if (!empty($this->sid)) {
            $page = 0;
            $series = Series::where('series_id', $this->sid)->first();
            if ($series) {
                $page = $series->page;
                $total_page = intval($series->count/10) + 1;
                if ($page>=$total_page && $series->count!=0) {
                    return;
                }
            }
            if ($page>0) {
                $next_page = intval($page)+1;
                $html = requests::get('https://book.douban.com/series/' . $this->sid.'?page='.$next_page);
            }else {
                $html = requests::get('https://book.douban.com/series/' . $this->sid);
            }
            if (!empty($html)) {
                $data = [];
                $data['series_id'] = $this->sid;
                $name = selector::select($html, "//h1");
                if (is_array($name)) {
                    $name = $name[0];
                }
                $data['name'] = $name;
                if (empty($name)){
                    return;
                }

                $press = selector::select($html, "//div[contains(@class, 'publishers')]");
                if (is_array($press)) {
                    $press = str_replace(["\r\n", "\n", "\r", "\t"], "", $press[0]);
                }else {
                    $press = str_replace(["\r\n", "\n", "\r", "\t"], "", $press);
                }
                $press = trim($press);
                $data['press'] = $press;
                if (empty($press)) {
                    return;
                }

                $count = selector::select($html, "//*[@class=\"clear-both\"]/text()");
                if (is_array($count)) {
                    foreach ($count as $c) {
                        $c = preg_replace('/[(\xc2\xa0)|\s]+/', '', $c);
                        $c = str_replace(["\r\n", "\n", "\r", "\t", " "], '', $c);
                        if (is_numeric($c)) {
                            $count = $c;
                        }
                    }
                }else{
                    $count = preg_replace('/[(\xc2\xa0)|\s]+/', '', $count);
                    $count = str_replace(["\r\n", "\n", "\r", "\t", " "], '', $count);
                }
                $data['count'] = $count;

                $recommend_count = selector::select($html, "//*[@class='rec-num']");
                if (is_array($recommend_count)) {
                    $recommend_count = str_replace(['人'], '', $recommend_count[0]);
                }else{
                    $recommend_count = str_replace(['人'], '', $recommend_count);
                }
                $data['recommend_count'] = $recommend_count;

                $subjectids = selector::select($html, "//*[@class='info']/h2/a/@href");
                if (is_array($subjectids)) {
                    $subjectids = array_map(function($s) {
                        return Tools::findNum($s);
                    }, $subjectids);
                }else{
                    $subjectids = [Tools::findNum($subjectids)];
                }
                $data['subjectids'] = join(',', $subjectids);

                if (count($subjectids)>0) {
                    if ($series) {
                        $this->update($series, $data);
                    }else{
                        $this->create($data);
                    }
                }

                Log::info('series_data '.$this->sid.'=> data='.json_encode($data, JSON_UNESCAPED_UNICODE));
            }else{
                Log::info('CrawlingSeriesJob '.$this->sid.' get nothing.');
                CrawlingSeriesJob::dispatch($this->sid)->delay(now()->addSecond(3));
            }
        }
    }

    function update(Series $series, $data) {
        $page = $series->page;
        $subjectids = $series->subjectids.','.$data['subjectids'];
        if ($data['name'] == 'Server Error') {
            CrawlingSeriesJob::dispatch($this->sid)->delay(now()->addSecond(2));
            return;
        }
        $series->update([
            'name' => $data['name'],
            'press' => $data['press'],
            'page' => $page+1,
            'subjectids' => $subjectids
        ]);
        $total_page = intval($series->count/10)+1;
        $crawled_page = $page+1;
        if ($crawled_page<$total_page) {
            CrawlingSeriesJob::dispatch($this->sid)->delay(now()->addSecond(2));
        }
    }

    function create($data) {
        if ($data['name'] == 'Server Error') {
            CrawlingSeriesJob::dispatch($this->sid)->delay(now()->addSecond(2));
            return;
        }
        $series = Series::create($data);
        $total_page = intval($series->count/10)+1;
        if ($total_page>1) {
            CrawlingSeriesJob::dispatch($this->sid)->delay(now()->addSecond(2));
        }
    }
}
