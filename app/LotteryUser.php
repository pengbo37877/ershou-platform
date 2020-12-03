<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LotteryUser extends Model
{
    protected $fillable = ['lottery_id', 'user_id', 'win', 'form_id', 'address_id', 'express_name',
        'express_no', 'ship_data'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function lottery()
    {
        return $this->belongsTo(Lottery::class);
    }
}
