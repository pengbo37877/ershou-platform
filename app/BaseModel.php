<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class BaseModel extends Model
{
    public function scopeWithCertain($query, $relation, Array $columns)
    {
        return $query->with([$relation => function ($query) use ($columns){
            $query->select(array_merge(['id'], $columns));
        }]);
    }
}
