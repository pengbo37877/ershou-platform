<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $fillable = ['author_id', 'name', 'avatar', 'gender', 'live_day', 'country', 'en_name', 'cn_name',
        'intro', 'hot_books', 'hot_start', 'new_books', 'new_start'];
}
