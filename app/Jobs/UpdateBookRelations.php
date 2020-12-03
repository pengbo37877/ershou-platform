<?php

namespace App\Jobs;

use App\Utils\Tools;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use phpspider\core\selector;

class UpdateBookRelations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $books;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->books = DB::select("select isbn from books where del_flag=0 limit 2");
        Log::info('更新图书推荐数据');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->books as $book) {
            $isbn = $book->isbn;
            $proxy = Cache::remember('data5u_proxy', 1, function () {
                return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1');
            });
            requests::set_proxy($proxy);
            $html = requests::get('https://book.douban.com/isbn/' . $isbn);
            $o = explode('/', requests::$info['url']);
            $subjectid = $o[count($o) - 2];
            $data = selector::select($html, "//dd/a/@href");
            if (is_array($data)) {
                $hs = array_map(function ($href) {
                    Tools::findNum($href);
                }, $data);
                $c = DB::select('select count(*) as c from books_relation where subjectid = ?', [$subjectid])[0]->c;
                if ($c==0) {
                    DB::insert('insert into books_relation (subjectid, subjectids) values (?, ?)', [$subjectid, join(',', $hs)]);
                }elseif ($c>0) {
                    DB::update('update books_relation set subjectids = ? where subjectid = ?', [join(',', $hs), $subjectid]);
                }
                Log::info($subjectid . '=>' . join(',', $hs));
                DB::update('update books set del_flag = 1 where isbn = ?', [$book->isbn]);
            }
        }
    }
}
