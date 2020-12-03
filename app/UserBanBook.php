<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserBanBook extends Model
{
    protected $fillable = ['user_id', 'book_id', 'subjectid', 'isbn'];
}
