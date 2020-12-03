<?php

namespace App\Listeners;

use App\Events\LotterySaving;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LotterySavingListener
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
     * @param  LotterySaving  $event
     * @return void
     */
    public function handle(LotterySaving $event)
    {
        $lottery = $event->lottery;
        if (!is_null($lottery->image) && !$this->checkUrl($lottery->image)) {
            $lottery->image = "http://pic.ovoooo.com/".$lottery->image;
        }
    }

    function checkUrl($C_url)
    {
        $str = "/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
        if (!preg_match($str, $C_url)) {
            return false;
        } else {
            return true;
        }
    }
}
