<?php

namespace App\Jobs;

use App\Book;
use App\BookSku;
use App\CartItem;
use App\Events\BookOnSale;
use App\ReminderItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Filesystem\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyUserBookOnSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user_id;
    public $book_id;

    /**
     * Create a new job instance.
     *
     * @param $user_id
     * @param $book_id
     */
    public function __construct($user_id, $book_id)
    {
        $this->user_id = $user_id;
        $this->book_id = $book_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        event(new BookOnSale($this->user_id, $this->book_id));
    }
}
