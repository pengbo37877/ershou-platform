<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookSale extends BaseModel
{
    protected $fillable = ['user_id', 'order_id', 'isbn', 'book_id', 'book_sku_id'];

    public function book_sku()
    {
        return $this->belongsTo(BookSku::class);
    }
}
