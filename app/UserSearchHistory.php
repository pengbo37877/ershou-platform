<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSearchHistory extends Model
{
    protected $fillable = ['user_id', 'q', 'book_ids', 'subjectids', 'start', 'total', 'search_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
