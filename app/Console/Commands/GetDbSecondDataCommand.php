<?php

namespace App\Console\Commands;

use App\Book;
use App\Jobs\GetDbSecondDataJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use phpspider\core\selector;

class GetDbSecondDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:second {isbn}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get douban.com second market data';

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
            $books = Book::select('subjectid')->where('other_prices', 0)->where('isbn', 'like', '9787%')->take(45)->get();
            $books->each(function ($book) {
                GetDbSecondDataJob::dispatch($book->subjectid)->delay(now()->addSecond(rand(0, 60)));
            });
        }else{
            $book = Book::where('isbn', $isbn)->first();
            if ($book) {
                GetDbSecondDataJob::dispatch($book->subjectid);
            }
        }
    }
}
