<?php

namespace App\Listeners;

use App\Events\ShudanSaving;
use App\Utils\Tools;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ShudanListener
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
     * @param  ShudanSaving  $event
     * @return void
     */
    public function handle(ShudanSaving $event)
    {
        $shudan = $event->shudan;
        if (!Tools::isUrl($shudan->cover)) {
            $shudan->cover = "http://pic.ovoooo.com/".$shudan->cover;
        }
    }
}
