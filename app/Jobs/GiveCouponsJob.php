<?php

namespace App\Jobs;

use App\Coupon;
use App\User;
use Carbon\Carbon;
use function foo\func;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class GiveCouponsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if (!$this->user->qr_scene || intval($this->user->qr_scene)==0){
            // 没有被邀请人
            DB::transaction(function(){
                // 查看自己关注后的两张coupon
                $coupons_count = Coupon::where('user_id', $this->user->id)
                    ->where('from_user', $this->user->qr_scene)->count();
                if ($coupons_count==0) {
                    $data = [
                        [
                            'user_id'   => $this->user->id,
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
                        ],
                        [
                            'user_id'       => $this->user->id,
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
                        ]
                    ];
                    // 批量插入
                    Coupon::insert($data);
                }
            });

        } else {
            // 有被邀请人
            DB::transaction(function(){
                // 查看自己关注后的两张coupon
                $coupons_count = Coupon::where('user_id', $this->user->id)
                    ->where('from_user', $this->user->qr_scene)->count();
                if ($coupons_count==0) {
                    $data = [
                        [
                            'user_id'   => $this->user->id,
                            'from'      => Coupon::FROM_USER_SHARE,
                            'from_user' => $this->user->qr_scene,
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
                        ],
                        [
                            'user_id'       => $this->user->id,
                            'from'          => Coupon::FROM_USER_SHARE,
                            'from_user'     => $this->user->qr_scene,
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
                        ],
                        [
                            'user_id'       => $this->user->qr_scene,
                            'from'          => Coupon::FROM_USER_SHARE,
                            'from_user'     => $this->user->id,
                            'name'          => '20元满减券',
                            'type'          => Coupon::TYPE_FIXED,
                            'order_type'    => Coupon::ORDER_TYPE_SALE,
                            'value'         => 20,
                            'used'          => 0,
                            'min_amount'    => 40,
                            'enabled'       => 0,   // 需下单激活
                            'code'          => Coupon::findAvailableCode(),
                            'not_after'     => Carbon::now()->addYears(5),
                            'created_at'    => Carbon::now(),
                            'updated_at'    => Carbon::now(),
                        ]
                    ];
                    // 批量插入
                    Coupon::insert($data);
                }
            });
        }




    }
}
