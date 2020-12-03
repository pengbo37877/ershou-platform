<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecoverReport extends Model
{
    protected $fillable = ['user_id', 'book_id', 'type', 'reason'];

    const TYPE_GOOD = 0;
    const TYPE_OUT_OF_PRINT = 1;
    const TYPE_SUIT = 2;

    public static $typeMap = [
        self::TYPE_GOOD => '内容好',
        self::TYPE_OUT_OF_PRINT => '绝版书',
        self::TYPE_SUIT => '套装书',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
