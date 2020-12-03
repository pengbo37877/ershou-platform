<?php

namespace App;

use App\UserAddress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Self_;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends BaseModel
{
    use SoftDeletes;

    const REFUND_STATUS_PENDING     = 'pending';
    const REFUND_STATUS_APPLIED     = 'applied';
    const REFUND_STATUS_PROCESSING  = 'processing';
    const REFUND_STATUS_SUCCESS     = 'success';
    const REFUND_STATUS_FAILED      = 'failed';

    const SHIPPER_SF    = 'SF';
    const SHIPPER_ZTO   = 'ZTO';

    const SHIP_STATUS_PENDING   = 'pending';
    const SHIP_STATUS_DELIVERED = 'delivered';
    const SHIP_STATUS_RECEIVED  = 'received';

    // 没有支付的订单是否已经关闭, 关联字段为closed
    const PAYING_STATUS_OPEN    = 0;
    const PAYING_STATUS_CLOSE   = 1;

    // 订单类型
    const ORDER_TYPE_RECOVER    = 1;
    const ORDER_TYPE_SALE       = 2;

    // 回收订单状态
    const RECOVER_STATUS_CANCEL     = -1;       // 用户取消
    const RECOVER_STATUS_PENDING    = 10;       // 下单成功
    const RECOVER_STATUS_VERIFIED   = 20;       // 回流鱼线上审核
    const RECOVER_STATUS_ARRANGE_EXPRESS = 30;  // 安排快递上门取书[从这里开始不能修改和取消订单]
    const RECOVER_STATUS_CHECKED    = 31;       // 回流鱼线上审核通过
    const RECOVER_STATUS_DELIVERED  = 40;       // 快递取书
    const RECOVER_STATUS_RECEIVED   = 50;       // 回流鱼收货
    const RECOVER_STATUS_PAYING     = 60;       // 回流鱼审核打款中
    const RECOVER_STATUS_COMPLETE   = 70;       // 订单完成/已打款

    // 卖书订单状态
    const SALE_STATUS_CANCEL    = -1;           // 用户取消[若已付款走退款子流程]
    const SALE_STATUS_PENDING   = 10;           // 下单成功
    const SALE_STATUS_PAID      = 20;           // 用户已付款
    const SALE_STATUS_STOCK_OUT = 30;           // 已出库[从这里开始不能修改和取消订单]
    const SALE_STATUS_ORDERED_EXPRESS   = 35;   // 已安排快递
    const SALE_STATUS_DELIVERED         = 40;   // 已发货
    const SALE_STATUS_COMPLETE          = 70;   // 已收货/订单完成


    const PAYMENT_WECHAT = '微信支付';
    const PAYMENT_WALLET = '余额支付';

    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING    => '未退款',
        self::REFUND_STATUS_APPLIED    => '已申请退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_SUCCESS    => '退款成功',
        self::REFUND_STATUS_FAILED     => '退款失败',
    ];

    public static $shipperDesc = [
        self::SHIPPER_SF    => '顺丰速运',
        self::SHIPPER_ZTO   => '中通快递'
    ];

    public static $shipStatusMap = [
        self::SHIP_STATUS_PENDING   => '未发货',
        self::SHIP_STATUS_DELIVERED => '已发货',
        self::SHIP_STATUS_RECEIVED  => '已收货',
    ];

    protected $fillable = [
        'no',
        'coupon_id',
        'prepay_id',
        'address',
        'address_id',
        'recover_time',
        'type',
        'total_amount',
        'remark',
        'paid_at',
        'payment_method',
        'payment_no',
        'refund_status',
        'refund_no',
        'closed',
        'reviewed',
        'express',          // 快递公司缩写
        'express_prev_no',  // 快递订单号
        'express_no',       // 快递运单号
        'ship_status',
        'ship_data',
        'ship_price',
        'recover_status',
        'sale_status',
        'extra',
        'is_evil',
        'order_id',
        'new_flag'
    ];

    protected $casts = [
        'closed'        => 'boolean',
        'reviewed'      => 'boolean',
        'total_amount'  => 'float',
        'extra'         => 'json',
        'ship_data'     => 'json'
    ];

    protected $dates = [
        'paid_at',
        'created_at',
        'deleted_at'
    ];

    protected static function boot()
    {
        parent::boot();
        // 监听模型创建事件，在写入数据库之前触发
        static::creating(function ($model) {
            // 如果模型的 no 字段为空
            if (!$model->no) {

                // 调用 findAvailableNo 生成订单流水号
                $model->no = static::findAvailableNo();

                // 如果生成失败，则终止创建订单
                if (!$model->no) {
                    return false;
                }
            }
        });
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class)
            ->with('book')
            ->with('bookSku.store_shelf');
    }

    public function allitems()
    {
        return $this->hasMany(OrderItem::class,'up_id')
            ->with('book')
            ->with('bookSku.store_shelf');
    }

    public function esitems(){
        return $this->hasMany(OrderItem::class,'up_id')->with('bookSku')->where('bookSku.ifnew','<>',1);
    }

    public function reviewed_items()
    {
        return $this->hasMany(OrderItem::class)
            ->where('review_result', 1)
            ->whereNotNull('hly_code')
            ->whereNotNull('title')
            ->where('level', '>=', 60);
    }

    public function rejected_items()
    {
        return $this->hasMany(OrderItem::class)
            ->where('review_result', 0);
    }

    public function ifup(){
        $orders = Order::where('order_id',$this->id)->first();
        return boolval($orders);
    }

    public function books()
    {
        if(!$this->ifup()){
            return $this->belongsToMany(Book::class, 'order_items')->withPivot(['price', 'review']);
        }else{
            return $this->belongsToMany(Book::class, 'order_items', 'up_id','book_id');
        }
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class, 'address_id')
            ->withTrashed();
    }

    public function refunds()
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function from_skus()
    {
        return $this->hasMany(BookSku::class, 'from_order');
    }

    public function to_skus()
    {
        return $this->hasMany(BookSku::class, 'to_order');
    }

    public function wallet()
    {
        return $this->hasMany(Wallet::class);
    }

    public static function findAvailableNo()
    {
        // 订单流水号前缀
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++) {

            // 随机生成 6 位的数字
            $no = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // 判断是否已经存在
            if (!static::query()->where('no', $no)->exists()) {
                return $no;
            }
            usleep(100);
        }
        Log::warning(sprintf('find order no failed'));

        return false;
    }

    public static function getAvailableRefundNo()
    {
        do {
            // Uuid类可以用来生成大概率不重复的字符串
            $no = Uuid::uuid4()->getHex();

            // 为了避免重复我们在生成之后在数据库中查询看看是否已经存在相同的退款订单号
        } while (self::query()->where('refund_no', $no)->exists());

        return $no;
    }

    // 订单点赞
    public function shudan_zan_users()
    {

        $dianzans = $this->hasMany(ShudanDianzan::class, 'comment_id')
            ->where('type', 2)
            ->where('status', 1);

        return $dianzans;
    }

    public function shudan_zan_status()
    {
        $dianzan = $this->hasMany(ShudanDianzan::class, 'comment_id')
            ->where('type', 2)
            ->where('status', 1);

        return $dianzan;
    }

    public function suborders(){
        return $this->hasMany(Order::class,'order_id');
    }
}
