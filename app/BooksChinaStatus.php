<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BooksChinaStatus extends Model
{
    protected $fillable = ['start_id', 'current_id', 'end_id'];
}
