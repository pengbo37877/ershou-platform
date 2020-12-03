<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BooksChina extends Model
{
    protected $fillable = ['books_china_id', 'name', 'author', 'press', 'series', 'price', 'china_price', 'isbn', 'binding', 'category',
        'summary', 'author_intro', 'catalog', 'publish_year', 'cover_image', 'cover_replace', 'page_num', 'weight', 'size',
        'rating_num', 'num_raters', 'recommendation'];
}
