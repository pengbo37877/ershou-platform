<?php

namespace App;

use App\Events\BookVersionSaving;
use Illuminate\Database\Eloquent\Model;

class BookVersion extends Model
{
    protected $fillable = ['book_id', 'title', 'name', 'price', 'cover', 'press', 'publish_year'];

    protected $dispatchesEvents = [
        'saving' => BookVersionSaving::class
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
