<?php

namespace App;

use App\Events\LotterySaving;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lottery extends Model
{
    protected $fillable = ['id', 'uuid', 'user_id', 'start_at', 'end_at', 'title', 'sub_title', 'image', 'desc', 'body', 'winner_count', 'end_count',
        'type', 'status', 'participants_count', 'notified', 'show_on_home', 'body_format'];

    protected $dates = ['start_at', 'end_at'];

    protected $appends = ['start_at_lunar', 'start_day'];

    protected $dispatchesEvents = [
        'saving' => LotterySaving::class,
    ];

    const TYPE_TIME = 0;
    const TYPE_COUNT = 1;

    public static $typeMap = [
        self::TYPE_TIME => '定时抽奖',
        self::TYPE_COUNT => '满人抽奖',
    ];

    const STATUS_NOT_START = 0;
    const STATUS_RUNNING = 1;
    const STATUS_END_WITH_RESULT = 2;
    const STATUS_END_WITH_NOTHING = 3;

    public static $statusMap = [
        self::STATUS_NOT_START => '没开始',
        self::STATUS_RUNNING => '进行中',
        self::STATUS_END_WITH_RESULT => '已结束',
        self::STATUS_END_WITH_NOTHING => '已结束，无人中奖',
    ];

    public function scopeOnHome($query)
    {
        return $query->where('show_on_home', 1);
    }

    public function scopePrivate($query)
    {
        return $query->where('show_on_home', 0);
    }

    public function getStartDayAttribute()
    {
        $start = Carbon::createFromTimeString($this->attributes['start_at']);
        if (is_null($start) || empty($start)){
            return null;
        }
        $year = $start->year;
        $month = $start->month;
        $day = $start->day;
        return $year.'年'.$month.'月'.$day.'日';
    }

    public function getStartAtLunarAttribute()
    {
        $start = Carbon::createFromTimeString($this->attributes['start_at']);
        if (is_null($start) || empty($start)){
            return null;
        }
        return (new Lunar())->convertSolarToLunar($start->year, $start->month, $start->day);
    }

    public function participants()
    {
        return $this->hasMany(LotteryUser::class);
    }

    public function winners()
    {
        return $this->hasMany(LotteryUser::class)->where('win', 1);
    }

    public static function findAvailableUuid($length = 8)
    {
        do {
            // 生成一个指定长度的随机字符串，并转成大写
            $code = strtoupper(Str::random($length));
            // 如果生成的码已存在就继续循环
        } while (self::query()->where('uuid', $code)->exists());

        return $code;
    }
}
