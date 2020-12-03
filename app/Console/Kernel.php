<?php

namespace App\Console;

use App\Jobs\AutoXiajiaSku;
use App\Jobs\CheckPaymentStatus;
use App\Jobs\CheckSaleItemCanRecover;
use App\Jobs\CopyXscBooks;
use App\Jobs\CrawlingBooksChina;
use App\Jobs\DownloadCoverImage;
use App\Jobs\FackSoldSkuResaleJob;
use App\Jobs\GetDoubanNewBookJob;
use App\Jobs\PendingBookFetch;
use App\Jobs\UpdateBookRelations;
use App\Jobs\UpdateOrderShipData;
use App\Jobs\ZtoCommonOrderSearchByCodeJob;
use App\Jobs\ZtoExposeServicePushOrderServiceJob;
use App\Jobs\ZtoOpenOrderCreateJob;
use App\Jobs\ZtoPartnerInsertSubmitAgentJob;
use App\Jobs\ZtoSubBillLogJob;
use App\Jobs\ZtoSubmitOrderCodeJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new UpdateOrderShipData)->hourly()->withoutOverlapping()->onOneServer();
        $schedule->job(new CheckPaymentStatus)->everyMinute()->withoutOverlapping()->onOneServer();
        $schedule->command('subscribe:ship random')->everyMinute()->withoutOverlapping()->onOneServer();
        $schedule->command('subscribe:today')->hourly()->withoutOverlapping()->onOneServer();
        //        $schedule->command('get:second random')->everyMinute()->withoutOverlapping();
        //        $schedule->command('get:subjectid')->everyFiveMinutes()->withoutOverlapping();
        //        $schedule->command('get:search random')->everyFiveMinutes()->withoutOverlapping();
        //        $schedule->command('copy:xsc')->everyMinute()->withoutOverlapping();
        //        $schedule->command('pending:fetch')->everyMinute()->withoutOverlapping();
        $schedule->command('download:cover')->everyMinute()->withoutOverlapping()->onOneServer();
        //        $schedule->command('crawling:china')->everyMinute()->withoutOverlapping();
        //        $schedule->command('get:new')->everyMinute()->withoutOverlapping();
        //        $schedule->command('book:snapshot')->dailyAt('23:50')->onOneServer();
        $schedule->command('lottery:open')->everyMinute()->withoutOverlapping()->onOneServer();
        $schedule->command('give:coupon')->everyMinute()->withoutOverlapping()->onOneServer();
        //        $schedule->command('enable:coupon')->everyMinute()->withoutOverlapping()->onOneServer();
        //        $schedule->command('get:list random')->everyMinute()->withoutOverlapping();
        //        $schedule->command('update:book random')->everyMinute()->withoutOverlapping();
        //        $schedule->command('update:2018')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('web:id random')->everyMinute()->withoutOverlapping()->onOneServer();
        //        $schedule->command('web2:id random')->everyMinute()->withoutOverlapping()->onOneServer();
        //        $schedule->command('web3:id random')->everyMinute()->withoutOverlapping()->onOneServer();
        //        $schedule->command('series:id random')->everyMinute()->withoutOverlapping();
        //        $schedule->command('author:id random')->everyMinute()->withoutOverlapping();
        $schedule->command('cancel:order random')->hourly()->withoutOverlapping()->onOneServer();
        $schedule->command('check:relation')->everyMinute()->withoutOverlapping()->onOneServer();
        $schedule->command('price:reduction')->dailyAt('9:00')->withoutOverlapping()->onOneServer();    // 图书调整价格
        $schedule->command('salediscount:update')->cron('* */2 * * *')->withoutOverlapping()->onOneServer();    // 更新图书最新折扣
        //$schedule->command('price:salediscount')->dailyAt('18:08')->withoutOverlapping()->onOneServer();
        $schedule->command('statistic')->dailyAt('3:30')->withoutOverlapping()->onOneServer();
        $schedule->command('price:shudan')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        $schedule->command('auto:sale')->everyMinute()->withoutOverlapping()->onOneServer();
        // $schedule->command('create:shudan random')->everyMinute()->withoutOverlapping()->onOneServer(); // 批量创建书单
        $schedule->job(new GetDoubanNewBookJob)->hourly()->withoutOverlapping()->onOneServer(); // 获取豆瓣最新的书籍
        $schedule->job(new FackSoldSkuResaleJob)->daily()->withoutOverlapping()->onOneServer(); // 把实际没有卖出的SKU重新上架
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
