<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientError extends Model
{
    protected $fillable = ['user_id', 'error', 'url'];
}
