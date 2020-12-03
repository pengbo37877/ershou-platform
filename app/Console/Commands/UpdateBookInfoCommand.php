<?php

namespace App\Console\Commands;

use App\Admin\Extensions\Tools\UpdateBookDoubanInfo;
use App\Book;
use App\Jobs\UpdateBookFromDouban;
use Illuminate\Console\Command;

class UpdateBookInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:book {isbn}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Book Info';

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
        if ($isbn == 'random'){
            $books = Book::where('author', '')->orderByDesc('created_at')->take(10)->get();
            $books->each(function($book) {
                UpdateBookFromDouban::dispatchNow($book);
            });
        }
    }
}
