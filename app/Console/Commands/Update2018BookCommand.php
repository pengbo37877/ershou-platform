<?php

namespace App\Console\Commands;

use App\Admin\Extensions\Tools\UpdateBookDoubanInfo;
use App\Book;
use Carbon\Carbon;
use App\Coupon;
use App\User;
use App\Jobs\CrawlingBookBySubjectId;
use App\Jobs\UpdateBookFromDouban;
use App\Order;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class Update2018BookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:2018';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Book which published at 2018';

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
        /**
        $books = Book::where('publish_year', '>=', '2018-01-01 00:00:00')->orderBy('publish_year')->take(50)->get();
        $books->each(function($book) {
            CrawlingBookBySubjectId::dispatch($book->subjectid)->delay(now()->addSecond(rand([0, 300])));
        });
         */

        /**
        $start = time();
        $low = 60000;
        $high = 70000;
        $users = User::select('id')->where('id', '>=', $low)->where('id', '<=', $high)
            ->get();


        $users->each(function($user)  {

            $order = Order::where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('recover_status', 70)
                        ->orWhere('sale_status', 70);
                })
                ->get();
            $order_count = count($order);

            $data = [];

            // 未下过订单
            if ($order_count == 0) {
                // 包邮券
                // 之前发过 10 块包邮券
                $sale_coupon = Coupon::where('user_id', $user->id)
                    ->where('value', '<', 15)
                    ->where('order_type', Coupon::ORDER_TYPE_SALE)
                    ->where('from', 'like', '%share%')
                    ->get();
                $sale_count = count($sale_coupon);

                if ($sale_count == 0) {
                    $data[] = [
                        'user_id'   => $user->id,
                        'from'      => Coupon::FROM_NO_SHARE,
                        'from_user' => 0,
                        'name'      => '5元包邮券',
                        'type'      => Coupon::TYPE_FIXED,
                        'order_type' => Coupon::ORDER_TYPE_SALE,
                        'value'         => 5,
                        'used'          => 0,
                        'min_amount'    => 20,
                        'enabled'       => 0,   // 需手动领取
                        'code'          => Coupon::findAvailableCode(),
                        'not_after'     => Carbon::now()->addYears(5),
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now(),
                    ];
                }

                // 增值券
                $recover_coupon = Coupon::where('user_id', $user->id)
                    ->where('value', 5)
                    ->where('order_type', Coupon::ORDER_TYPE_RECOVER)
                    ->where('from', 'like', '%share%')
                    ->get();
                $recover_count = count($recover_coupon);
                if ($recover_count == 0) {
                    $data[] = [
                        'user_id'       => $user->id,
                        'from'          => Coupon::FROM_NO_SHARE,
                        'from_user'     => 0,
                        'name'          => '5元增值券',
                        'type'          => Coupon::TYPE_FIXED,
                        'order_type'    => Coupon::ORDER_TYPE_RECOVER,
                        'value'         => 5,
                        'used'          => 0,
                        'min_amount'    => 20,
                        'enabled'       => 0,   // 需手动领取
                        'code' => Coupon::findAvailableCode(),
                        'not_after' => Carbon::now()->addYears(5),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }

                $count = count($data);

                if ($count > 0) {

                    DB::transaction(function() use ($data) {
                        Coupon::insert($data);
                    });

                    echo 2;
                } else {
                    echo 0;
                }
            } else {
                echo 'a';
            }


        });


        $end = time();
        $timespan = $end - $start;

        echo $timespan;
        */

        /**
         优惠券红点
        $coupons = Coupon::where('enabled', 1)
            ->where('used', 0)
            ->where('not_after', '<', now())
            ->get();
        $coupons->each(function($c){
            $user_id = $c->user_id;
            $user = User::find($user_id);

            if ($user && $user->email != 1) {
                $user->email = 1;
                $user->save();
                echo 0;
            } else {
                echo 1;
            }

        });
         */


    }
}
