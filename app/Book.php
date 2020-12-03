<?php

namespace App;

use App\Events\BookSaved;
use App\Events\BookSaving;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

class Book extends BaseModel
{
    const STATUS_ACCEPT = 1; // 收
    const STATUS_REJECT = 0; // 不收

    public static $statusMap = [
        self::STATUS_ACCEPT    => '收取',
        self::STATUS_REJECT    => '不收'
    ];

    const TYPE_DEFAULT = 0; // 正常
    const TYPE_OUT_OF_PRINT = 1; // 绝版 + 3折
    const TYPE_BAN = 100; // 禁书

    public static $typeMap = [
        self::TYPE_DEFAULT    => '默认',
        self::TYPE_OUT_OF_PRINT    => '绝版(加三折收)',
        self::TYPE_BAN    => '禁书',
    ];

    protected $fillable = [
        'id', 'isbn', 'name', 'cover_replace', 'can_recover', 'category', 'color', 'author', 'press',
        'publish_year', 'original_name', 'subtitile', 'translator', 'page_num', 'price', 'binding',
        'series', 'cover_image', 'rating_num', 'num_raters', 'summary', 'author_intro', 'catalog', 'subjectid',
        'publisher', 'discount', 'sale_discount', 'sale_discount_price', 'sale_sku_count', 'all_sku_count', 'jd_category', 'user_add',
        'group1', 'group2', 'group3', 'admin_user_id', 'type', 'reminder_count', 'other_prices', 'original_price',
        'series_id', 'volume_count', 'sale_item_count'
    ];

    protected $hidden = [
        'created_by', 'del_flag', 'jd_category',
        'original_name', 'remarks', 'status',
        'update_date', 'series'
    ];

    protected $casts = [
        'can_recover' => 'boolean'
    ];

    protected $dispatchesEvents = [
        'saving' => BookSaving::class,
        'saved' => BookSaved::class,
    ];

    public function all_skus()
    {
        return $this->hasMany(BookSku::class)->with('user');
    }

    // 可售的 sku
    public function for_sale_skus()
    {
        return $this->hasMany(BookSku::class)
            ->where('status', BookSku::STATUS_FOR_SALE);
    }

    // 全新
    public function for_sale_skus_level_100()
    {
        return $this->hasMany(BookSku::class)
            ->where('level', BookSku::LEVEL_100)
            ->where('status', BookSku::STATUS_FOR_SALE);
    }

    // 上好
    public function for_sale_skus_level_80()
    {
        return $this->hasMany(BookSku::class)
            ->where('level', BookSku::LEVEL_80)
            ->where('status', BookSku::STATUS_FOR_SALE);
    }

    // 中等
    public function for_sale_skus_level_60()
    {
        return $this->hasMany(BookSku::class)
            ->where('level', BookSku::LEVEL_60)
            ->where('status', BookSku::STATUS_FOR_SALE);
    }

    // 最近一本已卖的 sku
    public function latest_sold_sku()
    {
        return $this->hasMany(BookSku::class)
            ->where('status', BookSku::STATUS_SOLD)
            ->orderByDesc('to_order')
            ->limit(1);
    }

    // 已卖 的sku
    public function all_sold_sku()
    {
        return $this->hasMany(BookSku::class)
            ->where('status', BookSku::STATUS_SOLD);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function reminders()
    {
        return $this->hasMany(ReminderItem::class);
    }

    public function cart_items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function versions()
    {
        return $this->hasMany(BookVersion::class);
    }

    public function prices()
    {
        return $this->hasMany(BookPrice::class);
    }

    // 用户的评论
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // 书单
    public function shudans()
    {
        return $this->belongsToMany(Shudan::class, 'shudan_comments')->where('doulist_id', '>', 0);
    }
}
