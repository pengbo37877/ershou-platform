<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = ['series_id', 'name', 'press', 'count', 'page', 'desc', 'recommend_count', 'subjectids'];
}
