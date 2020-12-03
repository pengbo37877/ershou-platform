<?php

namespace App;

use App\Events\PictureSaving;
use Illuminate\Database\Eloquent\Model;

class Picture extends Model
{
    protected $fillable = ['image', 'tags'];

    protected $dispatchesEvents = [
        'saving' => PictureSaving::class
    ];
}
