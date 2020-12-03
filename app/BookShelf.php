<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookShelf extends BaseModel
{
    protected $fillable = ['user_id', 'isbn', 'book_id', 'book_sku_id'];
}
