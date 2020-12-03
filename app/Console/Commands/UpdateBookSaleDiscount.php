<?php

namespace App\Console\Commands;

use App\Book;
use App\BookSku;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateBookSaleDiscount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:salediscount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Book sale discount';

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
        $bs = DB::table('book_skus')
            ->select(DB::raw('book_id, min(price) as min_price'))
            ->where('status', 1)
            ->groupBy('book_id')
            ->get();

        $bs->each(function($b){
            $book_id = $b->book_id;
            $min_price = $b->min_price;

            $book = Book::find($book_id);
            $price = $book->price;

            if ($price > 0) {
                $sale_discount = ceil($min_price*100 / $price);

                $book->sale_discount = $sale_discount;
                $book->sale_discount_price = $min_price;

                $book->save();
            }


            usleep(10);
        });
    }
}
