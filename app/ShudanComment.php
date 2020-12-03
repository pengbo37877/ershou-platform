<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShudanComment extends Model
{
    const TYPE_SHUDAN = 1;
    const TYPE_PINGLUN = 2;

    protected $fillable = ['shudan_id', 'comment_id', 'book_id', 'use_cover', 'body'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function shudan()
    {
        return $this->belongsTo(Shudan::class);
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function for_sale_skus()
    {
        return $this->hasMany(BookSku::class, 'book_id', 'book_id')
            ->where('status', BookSku::STATUS_FOR_SALE);
    }
	
	public function shudan_zan_users()
	{
		return $this->hasMany(ShudanDianzan::class, 'comment_id', 'comment_id')
			->where('status', 1)
            ->where('type', 1);
		
	}
	
	public function shudan_zan_status()
	{
		return $this->hasMany(ShudanDianzan::class, 'comment_id', 'comment_id')
		    ->where('status', 1)
            ->where('type', 1);
	}
}
