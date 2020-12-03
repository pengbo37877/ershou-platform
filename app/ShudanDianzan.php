<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ShudanDianzan
 * 书单留言点赞
 */
class ShudanDianzan extends Model
{
	protected $table = 'shudan_dianzan';
    //protected $fillable = ['comment_id', 'user_id'];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
