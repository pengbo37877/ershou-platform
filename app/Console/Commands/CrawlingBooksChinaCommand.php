<?php

namespace App\Console\Commands;

use App\BooksChina;
use App\BooksChinaStatus;
use App\Jobs\CrawlingBooksChina;
use Illuminate\Console\Command;

class CrawlingBooksChinaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawling:china';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawling books china';

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
        for ($i=0;$i<6;$i++) {
//            CrawlingBooksChina::dispatch(now()->addSecond($i*10));
        }
    }
}
