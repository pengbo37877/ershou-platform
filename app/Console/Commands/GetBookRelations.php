<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpspider\core\requests;
use phpspider\core\selector;

class GetBookRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:relation {isbn}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get book relations from douban.com by isbn';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $isbn = $this->argument('isbn');
        if ($isbn == 'random') {
            $books = DB::select("select isbn from books where del_flag=0 order by id desc limit 20");
            foreach ($books as $book) {
                $this->updateOne($book->isbn);
            }
        }else{
            $this->updateOne($isbn);
        }
    }

    public function updateOne($isbn)
    {
        $proxy = Cache::remember('data5u_proxy', 1, function () {
            return file_get_contents('http://api.ip.data5u.com/dynamic/get.html?order=14f20a4ef24143de27b6eb17f70b44d1&sep=3');
        });
        requests::set_proxy($proxy);
        $html = requests::get('https://book.douban.com/isbn/'.$isbn);
        $o = explode('/', requests::$info['url']);
        $subjectid = $o[count($o)-2];
//        Log::info(requests::$info);
        $data = selector::select($html, "//dd/a/@href");
        if (is_array($data)) {
            $hs = array_map(function($href){
                $arr = explode('/', $href);
                return $arr[count($arr) - 2];
            }, $data);
            $c = DB::select('select count(*) as c from books_relation where subjectid = ?', [$subjectid])[0]->c;
            if ($c==0) {
                DB::insert('insert into books_relation (subjectid, subjectids) values (?, ?)', [$subjectid, join(',', $hs)]);
            }elseif ($c>0) {
                DB::insert('update books_relation set subjectids = ? where subjectid = ?', [join(',', $hs), $subjectid]);
            }
            Log::info($subjectid . '=>' . join(',', $hs));
        }
        DB::update('update books set del_flag = 1 where isbn = ?', [$isbn]);
    }
}
