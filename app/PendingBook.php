<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingBook extends BaseModel
{
    protected $fillable = ['user_id', 'isbn', 'reason', 'body'];

    const REASON_NO_PRICE = '价格没确定';
    const REASON_NO_ISBN = 'isbn未收录';
    const REASON_REJECT_RECOVER = '运营不收';
}
