<?php

namespace App\Console\Commands;

use App\Jobs\CrawlingByWebPageSubjectId;
use App\Jobs\CrawlingSeriesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CrawlingSeriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'series:id {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawling Series';

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
        $series_id = $this->argument('id');
        if ($series_id == 'random') {
            $gap = 8;
            $series_id = Cache::get('fetch-series');
            if ($series_id) {
                for ($i = 0; $i < $gap; $i++) {
                    $job = new CrawlingSeriesJob($series_id+$i);
                    dispatch($job)->delay(now()->addSecond($i*7));
                }
                Cache::increment('fetch-series', $gap);
            } else {
                Cache::forever('fetch-series', 1);
            }
        }else{
            CrawlingSeriesJob::dispatch($series_id)->delay(now()->addSecond(2));
        }
    }
}
