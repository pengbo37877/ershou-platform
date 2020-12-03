<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SkuPath extends BaseModel
{
    protected $fillable = ['book_sku_id', 'prev_user_id', 'user_id', 'is_owner'];

    public function book_sku()
    {
        return $this->belongsTo(BookSku::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prev_user()
    {
        return $this->belongsTo(User::class, 'prev_user_id');
    }
}
