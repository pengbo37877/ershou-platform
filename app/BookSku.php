<?php

namespace App;

use App\Events\BookSkuSaved;
use Illuminate\Database\Eloquent\Model;

class BookSku extends BaseModel
{
    const STATUS_NOT_FOR_SALE   = 0;    // 不上架
    const STATUS_FOR_SALE       = 1;    // 上架
    const STATUS_READY_TO_GO    = 2;    // 自动上架
    const STATUS_LOCKED_BY_USER = 3;    // 已锁定
    const STATUS_SOLD           = 4;    // 已卖
    const STATUS_RETREADING     = 5;    // 翻新中
    const STATUS_ISSUE          = 8;    // 有问题的

    const LEVEL_100 = 100;
    const LEVEL_99  = 99;
    const LEVEL_80  = 80;
    const LEVEL_60  = 60;
    const LEVEL_1   = 1;
    const LEVEL_NOT_FOR_SURE = 0;

    protected $fillable = ['user_id', 'title', 'description', 'original_price', 'recover_price', 'price', 'discount',
        'level', 'status', 'isbn', 'book_id', 'hly_code', 'groups', 'mark', 'book_version_id',
        'sale_at', 'sold_at', 'from_order', 'to_order', 'store_shelf_id', 'price_reduction_count','rating_num'];

    protected $dispatchesEvents = [
        'saved' => BookSkuSaved::class
    ];

    protected $dates = [
        'sale_at', 'sold_at', 'created_at', 'updated_at'
    ];

    public function from_order()
    {
        return $this->belongsTo(Order::class, 'from_order');
    }

    public function to_order()
    {
        return $this->belongsTo(Order::class, 'to_order');
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function scopeForSale($query)
    {
        return $query->where('status', BookSku::STATUS_FOR_SALE);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store_shelf()
    {
        return $this->belongsTo(StoreShelf::class);
    }

    public function cart_items()
    {
        return $this->hasMany(CartItem::class, 'book_sku_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items', 'book_sku_id', 'order_id');
    }

    public function getCategoryAttribute()
    {
        $book = $this->book;
        if ($book) {
            return Tag::whereIn('name', [
                $book->group1,
                $book->group2,
                $book->group3
            ])->get();
        }
        return Tag::take(1)->get();
    }

    public function setCategoryAttribute($value)
    {
        if (is_null($value) || count($value) == 0) return;
        $tags = Tag::whereIn('id', $value)->pluck('name')->toArray();
        $book = $this->book;

        $i = 0;
        foreach ($tags as $tag) {
            $i++;
            $book->update([
                'group'.$i => $tag
            ]);
        }
        if ($i==0) {
            $book->update([
                'group1' => null,
                'group2' => null,
                'group3' => null,
            ]);
        }else if($i==1) {
            $book->update([
                'group2' => null,
                'group3' => null,
            ]);
        }else if($i==2) {
            $book->update([
                'group3' => null,
            ]);
        }
    }

    public function prev_user()
    {
        return $this->belongsToMany(User::class, 'sku_paths', 'book_sku_id', 'prev_user_id')
            ->wherePivot('is_owner', 1);
    }

    public function curr_user()
    {
        return $this->belongsToMany(User::class, 'sku_paths', 'book_sku_id', 'user_id')
            ->wherePivot('is_owner', 1);
    }

    public function book_version()
    {
        return $this->belongsTo(BookVersion::class, 'book_version_id');
    }

    public function book_shop(){
        return $this->belongsTo(BookShop::class,'shop_id');
    }

    public function ship_rule(){
        return $this->belongsTo(ShipRule::class);
    }
}
