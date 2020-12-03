<?php

namespace App;

use App\UserAddress;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'nickname', 'sex', 'province', 'city', 'subscribe_time', 'subscribe_scene',
        'access_token', 'expires_in', 'refresh_token', 'mp_open_id', 'xcx_open_id', 'xcx_session', 'union_id', 'phone', 'avatar',
        'subscribe', 'user_agent', 'qr_scene', 'qr_scene_str', 'lottery_open_id', 'lottery_session'
    ];

    protected $dates = ['created_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'access_token', 'expires_in', 'refresh_token', 'phone'
    ];

    public function on_shelf_books()
    {
        return $this->belongsToMany(Book::class, 'book_shelves')
            ->withPivot('created_at')->orderBy('pivot_created_at', 'desc');
    }

    public function for_sale_books()
    {
        return $this->belongsToMany(Book::class, 'sale_items')
            ->withPivot('created_at')->wherePivot('deleted_at', null)->orderBy('pivot_created_at', 'desc');
    }

    public function sale_items()
    {
        return $this->hasMany(SaleItem::class)->with('book');
    }

    public function sold_books()
    {
        return $this->belongsToMany(Book::class, 'book_sales')
            ->with('for_sale_skus')->withPivot('created_at')->orderBy('pivot_created_at', 'desc');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function skus()
    {
        return $this->hasMany(BookSku::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->withPivot('created_at');
    }

    public function reminders()
    {
        return $this->hasMany(ReminderItem::class);
    }

    public function cart_items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function cart_skus()
    {
        return $this->belongsToMany(BookSku::class, 'cart_items');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class)->orderBy('last_used_at', 'desc');
    }

    public function wallet_items()
    {
        return $this->hasMany(Wallet::class);
    }

    public function toSearchableArray()
    {
        return $this->only('id','nickname', 'province', 'city');
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }
}
