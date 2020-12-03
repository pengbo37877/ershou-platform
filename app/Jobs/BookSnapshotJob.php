<?php

namespace App\Jobs;

use App\Book;
use App\BookSku;
use App\BookSnapshot;
use App\CartItem;
use App\ReminderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class BookSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $book;

    /**
     * Create a new job instance.
     *
     * @param Book $book
     */
    public function __construct(Book $book)
    {
        $this->book = $book;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Job中可以做数据库查询，但是查出的结果不能再次计算
        $reminder_count = ReminderItem::where('book_id', $this->book->id)->count();
        $cart_item_count = CartItem::where('book_id', $this->book->id)->count();
        $avg_sale_price  = DB::table('book_skus')->select(DB::raw('AVG(price) as avg_price'))->where('book_id', $this->book->id)
            ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO])->get()->pluck('avg_price')->first();
        BookSnapshot::create([
            'book_id' => $this->book->id,
            'isbn' => $this->book->isbn,
            'rating_num' => $this->book->rating_num,
            'num_raters' => $this->book->num_raters,
            'all_sku_count' => $this->book->all_sku_count,
            'sale_sku_count' => $this->book->sale_sku_count,
            'discount' => $this->book->discount,
            'can_recover' => $this->book->can_recover,
            'cart_item_count' => $cart_item_count,
            'avg_sale_price' => $avg_sale_price,
            'reminder_count' => $reminder_count
        ]);
    }
}
