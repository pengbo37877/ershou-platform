<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Taggable extends BaseModel
{
    protected $fillable = ['tag_id', 'taggable_id', 'taggable_type'];
}
