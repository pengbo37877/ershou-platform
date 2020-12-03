<?php

namespace App\Listeners;

use App\Events\SendNotifyMessage;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class SendNotifyMessageListener
{
    protected $app;

    /**
     * Create the event listener.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the event.
     *
     * @param  SendNotifyMessage  $event
     * @return void
     */
    public function handle(SendNotifyMessage $event)
    {
//        $openIds = ["ojrK40dDSJ8bLfFlCkQD0GcV2DhE","ojrK40SoCD9A0SVOmoByC6T0uaFo"];
//        $idsArray = DB::select('select mp_open_id from users where subscribe=1 order by updated_at desc limit 500000');
//        $openIds = array_map(function($id){
//            return $id->mp_open_id;
//        }, $idsArray);
        $this->app->broadcasting->sendText($event->msg);
    }
}
