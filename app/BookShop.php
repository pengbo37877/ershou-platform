<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookShop extends Model
{

    protected $fillable = ['shop_name','express','username','phone', 'addr','ship_price'];

    protected $dates = ['created_at', 'updated_at'];

    public function bookSkus(){
        return $this->hasMany(BookSku::class);
    }

    public function sale_items(){
        return $this->hasMany(OrderItem::class)->with('order')->where('order.sale_status',70);
    }

}
