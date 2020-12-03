<?php

namespace App;

use App\Events\ShudanSaving;
use Illuminate\Database\Eloquent\Model;

class Shudan extends BaseModel
{
    const STATUS_OPEN = 1;
    const STATUS_CLOSE = 0;

    protected $fillable = ['title', 'cover', 'color', 'desc', 'open', 'doulist_id'];

    protected $dispatchesEvents = [
        'saving' => ShudanSaving::class
    ];

    public function coverItems()
    {
        return $this->hasMany(ShudanComment::class)->where('use_cover', 1)->orderByDesc('updated_at');
    }

    public function items()
    {
        return $this->hasMany(ShudanComment::class);
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, ShudanComment::class);
    }

    public function books()
    {
        return $this->hasManyThrough(Book::class, ShudanComment::class);
    }

    public function covers()
    {
        return $this->hasManyThrough(Book::class, ShudanComment::class)->where('use_cover', 1);
    }
}
