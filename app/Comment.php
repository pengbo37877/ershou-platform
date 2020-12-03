<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['user_id', 'book_id', 'body', 'open'];

    protected $casts = [
        'open' => 'boolean'
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shudans()
    {
        return $this->belongsToMany(Shudan::class, 'shudan_comments');
    }
}
