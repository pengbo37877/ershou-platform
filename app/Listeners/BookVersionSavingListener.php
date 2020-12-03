<?php

namespace App\Listeners;

use App\Events\BookVersionSaving;
use App\Utils\Tools;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookVersionSavingListener
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
     * @param  BookVersionSaving  $event
     * @return void
     */
    public function handle(BookVersionSaving $event)
    {
        $bookVersion = $event->bookVersion;
        if ($bookVersion->cover && !Tools::isUrl($bookVersion->cover)) {
            $bookVersion->cover = "http://pic.ovoooo.com/".$bookVersion->cover;
        }
    }
}
