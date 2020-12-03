<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReminderItem extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'book_id', 'isbn', 'notify_times', 'open_times'];

    protected $dates = ['created_at'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
