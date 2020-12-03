<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShipRule extends Model
{
    protected $fillable = ['name','reject','base_price','content'];
}
