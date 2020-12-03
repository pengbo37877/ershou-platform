<?php

namespace App;

use App\Events\StoreShelfSaving;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreShelf extends Model
{
    protected $fillable = ['code', 'desc', 'capacity', 'unit'];

    const UNIT_BEN = 1;
    const UNIT_CUBIC_METERS = 2;

    public static $unitMap = [
        self::UNIT_BEN => '本',
        self::UNIT_CUBIC_METERS => '立方',
    ];

    protected $dispatchesEvents = [
        'saving' => StoreShelfSaving::class
    ];

    public function skus()
    {
        return $this->hasMany(BookSku::class)->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO]);
    }

    public static function findAvailableCode($length = 2)
    {
        do {
            // 生成一个指定长度的随机字符串，并转成大写
            $code = strtoupper(Str::random($length));
            // 如果生成的码已存在就继续循环
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
