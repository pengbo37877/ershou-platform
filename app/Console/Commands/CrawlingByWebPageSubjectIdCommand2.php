<?php

namespace App\Console\Commands;

use App\Jobs\CrawlingByWebPageSubjectId2;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CrawlingByWebPageSubjectIdCommand2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web2:id {id}';

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
            $gap = 120;
            $subjectid = Cache::get('fetch-subjectid2');
            if ($subjectid) {
                if ($subjectid>28000000) {
                    Log::info('CrawlingByWebPageSubjectIdCommand2 hit the top');
                    return;
                }
                for ($i = 0; $i < $gap; $i++) {
                    CrawlingByWebPageSubjectId2::dispatch($subjectid+$i)->delay(now()->addSecond($i%2));;
                }
                Cache::increment('fetch-subjectid2', $gap);
            } else {
                Cache::forever('fetch-subjectid2', 15000000);
            }
        }else{
            CrawlingByWebPageSubjectId2::dispatchNow($subjectid);
        }
    }
}
