<?php

namespace App\Listeners;

use App\Book;
use App\BookSku;
use App\Events\SaleItemSaved;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SaleItemSavedListener
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
     * @param  SaleItemSaved  $event
     * @return void
     */
    public function handle(SaleItemSaved $event)
    {
        $saleItem = $event->saleItem;
        $book = Book::find($saleItem->book_id);
        if ($book) {
            $book->can_recover = $saleItem->can_recover;
            $book->admin_user_id = 1;
            if ($saleItem->can_recover) {
                $book->discount = 10;
            }else{
                $book->discount = 0;
            }
            $book->save();
        }
    }
}
