<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends BaseModel
{
    protected $fillable = ['name'];

    public function books()
    {
        return $this->morphedByMany(Book::class, 'taggable');
    }

    public function users()
    {
        return $this->morphedByMany(User::class, 'taggable');
    }
}
