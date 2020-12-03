<?php

namespace App\Console\Commands;

use App\Jobs\SubscribeOrderShipData;
use App\Order;
use Illuminate\Console\Command;

class SubscribeOrderShipDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscribe:ship {order}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe ship data from kdniao';

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
        $orderId = $this->argument('order');
        if ($orderId == 'random') {
            $sale_orders = Order::where('closed', 0)->where('type', Order::ORDER_TYPE_SALE)
                ->whereNotIn('sale_status', [Order::SALE_STATUS_CANCEL, Order::SALE_STATUS_COMPLETE])->whereNotNull('express')->whereNotNull('express_no')
                ->whereNull('extra')->where('created_at', '>', now()->subDays(7)->toDateTimeString())->get();

            foreach ($sale_orders as $order) {
                SubscribeOrderShipData::dispatch($order);
            }

            $recover_orders = Order::where('closed', 0)->where('type', Order::ORDER_TYPE_RECOVER)
                ->whereNotIn('recover_status', [Order::RECOVER_STATUS_CANCEL, Order::RECOVER_STATUS_COMPLETE])->whereNotNull('express')->whereNotNull('express_no')
                ->whereNull('extra')->where('created_at', '>', now()->subDays(7)->toDateTimeString())->get();

            foreach ($recover_orders as $order) {
                SubscribeOrderShipData::dispatch($order);
            }
        } else {
            $order = Order::find($orderId);
            if ($order) {
                SubscribeOrderShipData::dispatch($order);
            }
        }
    }
}
