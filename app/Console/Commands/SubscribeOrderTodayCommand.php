<?php

namespace App\Console\Commands;

use App\Jobs\SubscribeOrderShipData;
use App\Order;
use Illuminate\Console\Command;

class SubscribeOrderTodayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscribe:today';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe ship data from kdniao only today orders';

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
        $sale_orders = Order::where('closed', 0)->where('type', Order::ORDER_TYPE_SALE)
            ->where('sale_status', '>=', Order::SALE_STATUS_STOCK_OUT)->whereNotNull('express')->whereNotNull('express_no')
            ->get();

        foreach ($sale_orders as $order) {
            SubscribeOrderShipData::dispatch($order);
        }

        $recover_orders = Order::where('closed', 0)->where('type', Order::ORDER_TYPE_RECOVER)
            ->where('recover_status', Order::RECOVER_STATUS_ARRANGE_EXPRESS)->whereNotNull('express')->whereNotNull('express_no')
            ->get();

        foreach ($recover_orders as $order) {
            SubscribeOrderShipData::dispatch($order);
        }
    }
}
