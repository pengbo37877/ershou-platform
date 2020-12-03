<?php

namespace App\Console\Commands;

use App\Jobs\CancelSaleOrderIn15Minutes;
use App\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CancelOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancel:order {order}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel sale order which is not paid';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $order = $this->argument('order');
        if ($order == 'random') {
            $orders = Order::where('type', Order::ORDER_TYPE_SALE)
                ->whereNull('order_id')
                ->whereNull('paid_at')
                ->where('closed', 0)
                ->where('sale_status', '<>', Order::SALE_STATUS_CANCEL)
                ->where('created_at', '<', now()->subHours(2))
                ->get();

            $orders->each(function ($o) {
                CancelSaleOrderIn15Minutes::dispatch($o)->onQueue('high');
            });
        } else {
            $o = Order::find($order);
            if (
                $o->type == Order::ORDER_TYPE_SALE
                    && $o->closed == 0
                    && $o->sale_status != Order::SALE_STATUS_CANCEL
                    && empty($o->paid_at) &&

                Carbon::createFromTimeString($o->created_at->toDateTimeString())->lt(now()->subHours(2))
            ) {

                CancelSaleOrderIn15Minutes::dispatch($o)->onQueue('high');
            }
        }
    }
}
