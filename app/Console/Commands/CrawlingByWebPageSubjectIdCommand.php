<?php

namespace App\Console\Commands;

use App\Book;
use App\Jobs\CrawlingByWebPageSubjectId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrawlingByWebPageSubjectIdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:id {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawling By WebPage SubjectId';

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
        $subjectid = $this->argument('id');
        if ($subjectid == 'random') {
//            $gap = 120;
//            $subjectid = Cache::get('fetch-subjectid');
//            if ($subjectid) {
//                if ($subjectid>10000000) {
//                    Log::info('CrawlingByWebPageSubjectIdCommand hit the top');
//                    return;
//                }
//                for ($i = 0; $i < $gap; $i++) {
//                    CrawlingByWebPageSubjectId::dispatch($subjectid+$i)->delay(now()->addSecond(rand(0, 60)));;
//                }
//                Cache::increment('fetch-subjectid', $gap);
//            } else {
//                Cache::forever('fetch-subjectid', 1000000);
//            }
            // 更新relation
//            $ids = DB::table("books_relation")
//                ->where('subjectids', 'like', ',%')
//                ->where('status', 0)
//                ->take(60)->get();
            $books = Book::where('rating_num','>=',6)->where('author_intro', "")
                ->where('user_add', '<', 5)->take(60)->get();
            if (count($books)>0) {
                for ($i=0;$i<count($books);$i++) {
                    $book = $books->get($i);
                    DB::update('update books set user_add=5 where id=?', [$book->id]);
                    CrawlingByWebPageSubjectId::dispatch($book->subjectid)->delay(now()->addSecond($i*2));
                }
            }
        }else{
            CrawlingByWebPageSubjectId::dispatchNow($subjectid);
        }
    }
}
