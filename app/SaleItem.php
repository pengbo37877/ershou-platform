<?php

namespace App;

use App\Events\SaleItemSaved;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends BaseModel
{
    use SoftDeletes;
    // 回收书item
    protected $fillable = ['user_id', 'book_id', 'isbn', 'book_sku_id', 'can_recover', 'remind_count', 'sale_sku_count', 'show'];

    protected $dates = ['deleted_at'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sku()
    {
        return $this->belongsTo(BookSku::class);
    }

    public function recover_reports()
    {
        return $this->hasMany(RecoverReport::class, 'book_id', 'book_id');
    }
}
