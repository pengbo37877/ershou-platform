<?php

namespace App\Console\Commands;

use App\Jobs\CrawlingAuthorJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CrawlingAuthorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'author:id {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawling Author';

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
        $author_id = $this->argument('id');
        if ($author_id == 'random') {
            $gap = 4;
            $author_id = Cache::get('fetch-author');
            if ($author_id) {
                for ($i = 0; $i < $gap; $i++) {
                    $job = new CrawlingAuthorJob($author_id+$i);
                    dispatch($job)->delay(now()->addSecond($i*10));
                }
                Cache::increment('fetch-author', $gap);
            } else {
                Cache::forever('fetch-author', 1000000);
            }
        }else{
            CrawlingAuthorJob::dispatch($author_id)->delay(now()->addSecond(2));
        }
    }
}
