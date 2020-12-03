<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Wallet extends BaseModel
{
    const TYPE_SALE_BOOK = 1;
    const TYPE_BUY_BOOK = 2;
    const TYPE_TRANSFER_OUT = 3;
    const TYPE_TRANSFER_IN = 4;
    const TYPE_BUY_BOOK_REFUND = 5;

    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    protected $fillable = ['user_id', 'order_id', 'type', 'status', 'amount', 'memo', 'result'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function getAvailableTransferNo()
    {
        do {
            // Uuid类可以用来生成大概率不重复的字符串
            $no = Uuid::uuid4()->getHex();
            // 为了避免重复我们在生成之后在数据库中查询看看是否已经存在相同的退款订单号
        } while (self::query()->where('memo', $no)->exists());

        return $no;
    }
}
