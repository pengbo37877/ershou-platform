<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Button extends Model
{
    public function sub_button(){
        return $this->hasMany(Button::class,'btn_id');
    }
}
