<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItem extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'book_id', 'book_sku_id', 'amount', 'selected', 'source'];

    protected $dates = ['created_at', 'deleted_at'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function book_sku()
    {
        return $this->belongsTo(BookSku::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
