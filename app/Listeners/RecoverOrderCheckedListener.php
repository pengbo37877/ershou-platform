<?php

namespace App\Listeners;

use App\Events\RecoverOrderChecked;
use App\Order;
use App\OrderItem;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecoverOrderCheckedListener
{
    protected $app;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the event.
     *
     * @param  RecoverOrderChecked  $event
     * @return void
     */
    public function handle(RecoverOrderChecked $event)
    {
        $order=$event->order;
        $items = $order->items;
        $acceptItems = $items->where('review_result', OrderItem::REVIEW_OK);
        $rejectItems = $items->where('review_result', OrderItem::REVIEW_REJECT);
        if (env('SEND_WECHAT_MSG')) {
            $this->app->template_message->send([
                'touser' => $order->user->mp_open_id,
                'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                'url' => env('APP_URL') . '/wechat/recover_order/' . $order->no,
                'data' => [
                    'first' => '你的卖书单共' . count($items) . '本书，回流鱼拒收了' . count($rejectItems) . '本，收取' . count($acceptItems) . '本',
                    'keyword1' => $order->no,
                    'keyword2' => '已审核通过',
                    'keyword3' => Carbon::now()->toDateTimeString()
                ]
            ]);
        }
    }
}
