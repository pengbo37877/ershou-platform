<?php

namespace App\Listeners;

use App\Events\PictureSaving;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class PictureSavingListener
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
     * @param  PictureSaving  $event
     * @return void
     */
    public function handle(PictureSaving $event)
    {
        $picture = $event->picture;
        if ($picture->image && !$this->checkUrl($picture->image)) {
            $picture->image = "http://pic.ovoooo.com/".$picture->image;
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
