<?php

namespace App;

use App\Events\OrderItemSaved;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class OrderItem extends BaseModel
{
    protected $fillable = ['order_id', 'book_id', 'book_sku_id', 'book_version_id', 'amount', 'price', 'rating', 'review_result',
        'review', 'reviewed_at', 'reviewed_price', 'sale_price', 'remind_count', 'cart_item_count', 'sale_sku_count', 'level', 'title', 'groups',
        'title_array', 'group_array', 'hly_code', 'source', 'is_add','up_id'];
    protected $dates = ['reviewed_at'];

    protected $appends = ['title_array', 'group_array'];

    const REVIEW_OK = 1;
    const REVIEW_REJECT = 0;

    public static $reviewMap = [
        self::REVIEW_OK => '通过',
        self::REVIEW_REJECT => '拒绝',
    ];

    public function getTitleArrayAttribute()
    {
        $title = $this->attributes['title'];
        if (empty($title)) {
            return [];
        }else{
            return explode(',', $title);
        }
    }
    
    public function sold_skus(){
        return $this->hasMany(BookSku::class,'book_id','book_id')->where('status',4);
    }

    public function storage_skus()
    {
        return $this->hasMany(BookSku::class, 'book_id', 'book_id')->whereBetween('status', [1, 2]);
    }

    public function setTitleArrayAttribute($value)
    {
        $this->attributes['title'] = implode(',', $value);
    }

    public function getGroupArrayAttribute()
    {
        $group = $this->attributes['groups'];
        if (empty($group)) {
            $book = Book::find($this->attributes['book_id']);
            $arr = [];
            if (!empty($book->group1)) {
                array_push($arr, $book->group1);
            }
            if (!empty($book->group2)) {
                array_push($arr, $book->group2);
            }
            if (!empty($book->group3)) {
                array_push($arr, $book->group3);
            }
            return $arr;
        }else{
            return explode(',', $group);
        }
    }

    public function setGroupArrayAttribute($value)
    {
        $this->attributes['groups'] = implode(',', $value);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function bookSku()
    {
        return $this->belongsTo(BookSku::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function book_version()
    {
        return $this->belongsTo(BookVersion::class, 'book_version_id');
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
}
