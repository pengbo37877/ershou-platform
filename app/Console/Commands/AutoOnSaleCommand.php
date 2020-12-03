<?php

namespace App\Console\Commands;

use App\BookSku;
use App\Events\BookRecoverPriceRisen;
use App\Order;
use Illuminate\Console\Command;

class AutoOnSaleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:sale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto put sku on sale';

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
        $Skus = BookSku::with('book')->where('status', BookSku::STATUS_READY_TO_GO)->get();
        foreach ($Skus as $sku) {
            $book = $sku->book;
            if ($book->sale_sku_count==0) {
                $sku->status = BookSku::STATUS_FOR_SALE;
                $sku->save();
                $book->sale_sku_count = BookSku::where('status', BookSku::STATUS_FOR_SALE)->count();
                $book->save();
            }
        }
        $sold_skus = BookSku::with('book')->with('to_order')
            ->where('status', BookSku::STATUS_SOLD)
            ->where('sold_at', '>', now()->subDays(3))
            ->get();
        foreach ($sold_skus as $sku) {
            $book = $sku->book;
            $to_order = $sku->to_order;
            if ($to_order->sale_status == Order::SALE_STATUS_CANCEL) {
                $sku->status = BookSku::STATUS_FOR_SALE;
                $sku->sold_at = '';
                $sku->to_order = '';
                $sku->save();
                $book->sale_sku_count = $book->sale_sku_count + 1;
                $book->save();
            }
        }
    }
}
