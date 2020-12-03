<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvilPhone extends Model
{
    use SoftDeletes;

    protected $fillable = ['username','phone','user_id','order_id'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function address(){
        return $this->belongsTo(UserAddress::class);
    }
}
