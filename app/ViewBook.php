<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ViewBook extends Model
{
    protected $fillable = ['book_id', 'user_id', 'source', 'second','content'];
}
