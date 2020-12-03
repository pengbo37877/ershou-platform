<?php

namespace App\Console\Commands;

use App\Book;
use App\BookSku;
use App\CartItem;
use App\Events\BookOnSale;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SaleDiscountUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salediscount:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Books sale_discount';

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
        $start = time();
        $bs = BookSku::select('id', 'book_id')
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->get();


        $bs->each(function($sku)  {

            // 调价时更新图书最低折扣
            $book_id = $sku->book_id;
            $book = Book::find($book_id);
            $book_skus = BookSku::where('book_id', $book_id)
                ->where('status', BookSku::STATUS_FOR_SALE)
                ->get();

            $price = intval($book->price);
            if (count($book_skus) > 0) {
                $min_price = $book_skus->min('price');
            } else {
                $min_price = $price;
            }

            $sale_discount = 0;
            if ($price > 0) {
                $sale_discount = intval($min_price * 100 / $book->price);
            }

            // 图书在售数量
            // $sale_sku_count = count($book_skus);
            //$book->sale_sku_count = $sale_sku_count;

            if ($sale_discount != $book->sale_discount) {
                echo $sku->id . '.';
                // 更新图书 最低折扣和 折扣价格

                $book->sale_discount        = $sale_discount;
                $book->sale_discount_price  = $min_price;
                $book->save();

            }

        });

        $end = time();
        $timespan = $end - $start;

        echo $timespan;
    }
}
