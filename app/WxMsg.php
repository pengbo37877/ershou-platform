<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WxMsg extends Model
{
    protected $fillable = ['body'];

    protected $casts = [
        'body' => 'json'
    ];
}
