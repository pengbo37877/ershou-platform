<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Juzi extends Model
{
    protected $fillable = ['body', 'author', 'book_name', 'picture_id', 'user_id', 'book_id'];

    public function picture()
    {
        return $this->belongsTo(Picture::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
