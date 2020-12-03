<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DouList extends Model
{
    protected $fillable = ['doulist_id', 'name', 'desc', 'start', 'book_count', 'following_count', 'recommend_count', 'subjectids', 'generated'];
}
