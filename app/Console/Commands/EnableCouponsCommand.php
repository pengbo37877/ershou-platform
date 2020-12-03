<?php

namespace App\Console\Commands;

use App\Jobs\EnableCouponJob;
use App\Jobs\GiveCouponsJob;
use App\Order;
use App\User;
use Illuminate\Console\Command;

class EnableCouponsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enable:coupon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'enable user coupons';

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
        $orders = Order::with('user')->where('closed', 0)->orderByDesc('updated_at')->take(60)->get();
        for ($i=0;$i<count($orders);$i++) {
            $order = $orders->get($i);
            EnableCouponJob::dispatch($order)->delay(now()->addSecond($i));
        }
    }
}
