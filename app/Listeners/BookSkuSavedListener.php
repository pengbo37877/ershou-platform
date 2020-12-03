<?php

namespace App\Listeners;

use App\BookSku;
use App\Events\BookSkuSaved;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class BookSkuSavedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  BookSkuSaved  $event
     * @return void
     */
    public function handle(BookSkuSaved $event)
    {
        $bookSku = $event->bookSku;
        $count = BookSku::where('book_id', $bookSku->book_id)->count();
        DB::update('update books set all_sku_count=? where id=?', [$count, $bookSku->book_id]);
    }
}
