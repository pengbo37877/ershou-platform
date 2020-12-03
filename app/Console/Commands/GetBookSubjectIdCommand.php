<?php

namespace App\Console\Commands;

use App\Admin\Extensions\Tools\UpdateBookDoubanInfo;
use App\Book;
use App\Jobs\UpdateBookFromDouban;
use Illuminate\Console\Command;

class GetBookSubjectIdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:subjectid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get book subject id of douban.com';

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
        $books = Book::whereNull('subjectid')->where('user_add', 1037)->take(200)->get();
        $books->each(function ($book) {
            UpdateBookFromDouban::dispatch($book)->delay(now()->addSecond(rand(0, 300)));
        });
    }
}
