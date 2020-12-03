<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'province', 'city', 'district', 'address', 'zip', 'contact_name', 'contact_phone', 'last_used_at', 'is_default'
    ];
    protected $dates = ['last_used_at'];
    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute()
    {
        return "{$this->province} {$this->city} {$this->district} {$this->address}";
    }
}
