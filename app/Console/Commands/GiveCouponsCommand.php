<?php

namespace App\Console\Commands;

use App\Jobs\GiveCouponsJob;
use App\User;
use Illuminate\Console\Command;

class GiveCouponsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:coupon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give new user coupons';

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
        $users = User::whereNotNull('qr_scene')->where('qr_scene', '>', 0)->orderByDesc('created_at')->take(300)->get();
        $users->each(function ($user) {
            GiveCouponsJob::dispatchNow($user);
        });
    }
}
