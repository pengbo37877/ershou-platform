<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRecommend extends Model
{
    protected $fillable = ['user_id', 'subjectids'];
}
