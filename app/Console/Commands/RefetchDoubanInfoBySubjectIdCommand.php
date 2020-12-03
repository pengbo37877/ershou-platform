<?php

namespace App\Console\Commands;

use App\Jobs\CrawlingBookBySubjectId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefetchDoubanInfoBySubjectIdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:book';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch book info from douban';

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
        $gap = 80;
        $subjectid = Cache::get('fetch-subjectid');
        if ($subjectid) {
            for ($i = 0; $i < $gap; $i++) {
//                CrawlingBookBySubjectId::dispatch($subjectid+$i)->delay(now()->addSecond(rand(0, 60)));;
            }
            Cache::increment('fetch-subjectid', $gap);
        } else {
            Cache::forever('fetch-subjectid', 1000000);
        }
    }
}
