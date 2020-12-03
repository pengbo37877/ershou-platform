<?php

namespace App;

use App\Events\CouponSaving;
use App\Exceptions\CouponCodeUnavailableException;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Coupon extends Model
{
    // 用常量的方式定义支持的现金券类型
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENT = 'percent';

    public static $typeMap = [
        self::TYPE_FIXED   => '固定金额',
        self::TYPE_PERCENT => '比例',
    ];

    // 用于买书还是用于卖书
    const ORDER_TYPE_SALE = 'sale'; // 买书
    const ORDER_TYPE_RECOVER = 'recover'; // 卖书

    public static $orderTypeMap = [
        self::ORDER_TYPE_SALE   => '用户买书',
        self::ORDER_TYPE_RECOVER => '用户卖书',
    ];

    const FROM_USER_SHARE = 'from_user_share';
    const FROM_NO_SHARE = 'from_no_share';

    public static $fromTypeMap = [
        self::FROM_USER_SHARE   => '来自用户分享',
    ];

    protected $fillable = [
        'user_id',
        'from',
        'from_user',
        'name',
        'code',
        'type',
        'order_type',
        'value',
        'used',
        'min_amount',
        'not_before',
        'not_after',
        'enabled',
    ];
    // 指明这两个字段是日期类型
    protected $dates = ['not_before', 'not_after'];

    protected $appends = ['description'];

    protected $dispatchesEvents = [
        'saving' => CouponSaving::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function from()
    {
        return $this->belongsTo(User::class,'from_user');
    }

    public function getDescriptionAttribute()
    {
        $str = '';
        if ($this->order_type == $this::ORDER_TYPE_SALE) {
            if ($this->min_amount > 0) {
                //$str = '满' . str_replace('.00', '', $this->min_amount);
                if ($this->value == 5) {
                    $str = '买书下单时抵扣邮费';
                } else {
                    $str = '买书下单时抵扣现金';
                }

                return $str;
            }
            if ($this->type === self::TYPE_PERCENT) {
                return $str . '优惠' . str_replace('.00', '', $this->value) . '%';
            }

            return $str . '减' . str_replace('.00', '', $this->value);
        }else{
            if ($this->min_amount > 0) {
                //$str = '满' . str_replace('.00', '', $this->min_amount);
                return '卖书下单时现金奖励';
            }
            if ($this->type === self::TYPE_PERCENT) {
                return $str . '加价' . str_replace('.00', '', $this->value) . '%';
            }

            return $str . '加' . str_replace('.00', '', $this->value);
        }
    }

    public function checkAvailable($orderAmount = null, $ship_price = null)
    {
        if (!boolval($this->enabled)) {
            throw new Exception('现金券未激活');
        }

        if (boolval($this->used)) {
            throw new Exception('该现金券已使用');
        }

        if ($this->not_before && $this->not_before->gt(Carbon::now())) {
            throw new Exception('该现金券现在还不能使用');
        }

        if ($this->not_after && Carbon::now()->gt($this->not_after)) {
            throw new Exception('该现金券已过期');
        }

        if (!is_null($orderAmount) && $orderAmount < $this->min_amount) {
            throw new Exception('订单金额不满足该现金券最低金额');
        }

        if(!is_null($orderAmount) && $orderAmount >= 99 && $this->order_type == "sale" && $this->value == 5 && $ship_price == 0){
            throw new Exception('订单金额已满足包邮条件，不能使用包邮券');
        }
    }

    public function getSaleAdjustedPrice($orderAmount)
    {
        // 固定金额
        if ($this->type === self::TYPE_FIXED) {
            // 为了保证系统健壮性，我们需要订单金额最少为 0.01 元
            return max(0.01, $orderAmount - $this->value);
        }

        return number_format($orderAmount * (100 - $this->value) / 100, 2, '.', '');
    }

    public function getRecoverAdjustedPrice($orderAmount)
    {
        // 固定金额
        if ($this->type === self::TYPE_FIXED) {
            // 为了保证系统健壮性，我们需要订单金额最少为 0.01 元
            return max(0.01, $orderAmount + $this->value);
        }

        return number_format($orderAmount * (100 + $this->value) / 100, 2, '.', '');
    }

    public static function findAvailableCode($length = 16)
    {
        do {
            // 生成一个指定长度的随机字符串，并转成大写
            $code = strtoupper(Str::random($length));
            // 如果生成的码已存在就继续循环
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
