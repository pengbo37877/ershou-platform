<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookSnapshot extends Model
{
    protected $fillable = ['book_id', 'isbn', 'reminder_count', 'sale_sku_count', 'all_sku_count', 'cart_item_count',
        'rating_num', 'num_raters', 'discount', 'avg_sale_price', 'can_recover'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
