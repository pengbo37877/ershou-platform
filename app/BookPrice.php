<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookPrice extends Model
{
    protected $fillable = ['isbn', 'dzy_price', 'dzy_new_price', 'dd_new_price', 'amz_new_price', 'jd_new_price', 'bc_new_price',
        'douban_es_low', 'douban_es_high', 'douban_es_count', 'douban_es_want_count', 'created_at', 'updated_at'];
}
