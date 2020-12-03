<?php

namespace App\Console\Commands;

use App\Jobs\CrawlingByWebPageSubjectId2;
use App\Jobs\CrawlingByWebPageSubjectId3;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CrawlingByWebPageSubjectIdCommand3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web3:id {id}';

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
            $subjectid = Cache::get('fetch-subjectid3');
            if ($subjectid) {
                for ($i = 0; $i < $gap; $i++) {
                    CrawlingByWebPageSubjectId3::dispatch($subjectid+$i);
                }
                Cache::increment('fetch-subjectid3', $gap);
            } else {
                Cache::forever('fetch-subjectid3', 28000000);
            }
        }else{
            CrawlingByWebPageSubjectId3::dispatchNow($subjectid);
        }
    }
}
