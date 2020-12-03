<?php

namespace App\Console\Commands;

use App\Book;
use App\Jobs\BookSnapshotJob;
use Illuminate\Console\Command;

class BookSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:snapshot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Snapshot of on sale books';

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
        $books = Book::select('id', 'isbn', 'rating_num', 'num_raters', 'sale_sku_count', 'all_sku_count', 'can_recover', 'discount')
            ->where(function ($q) {
                $q->where('all_sku_count', '>', 0)->orWhere('reminder_count', '>', 0);
            })->get();
        $books->each(function($book) {
            BookSnapshotJob::dispatch($book)->delay(now()->addSecond(random_int(0, 600)));
        });
    }
}
