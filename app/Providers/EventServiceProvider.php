<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\OrderCanceled' => [
            'App\Listeners\OrderCanceledListener',
        ],
        'App\Events\OrderPaid' => [
            'App\Listeners\OrderPaidListener',
        ],
        'App\Events\OrderStockOut' => [
            'App\Listeners\OrderStockOutListener',
        ],
        'App\Events\OrderShipped' => [
            'App\Listeners\OrderShippedListener',
        ],
        'App\Events\OrderDelivered' => [
            'App\Listeners\OrderDeliveredListener',
        ],
        'App\Events\OrderCompleted' => [
            'App\Listeners\OrderCompletedListener',
        ],
        'App\Events\OrderCreated' => [
            'App\Listeners\OrderCreatedListener',
        ],
        'App\Events\OrderClosed' => [
            'App\Listeners\OrderClosedListener',
        ],
        'App\Events\OrderSigned' => [
            'App\Listeners\OrderSignedListener',
        ],
        'App\Events\BookSaving' => [
            'App\Listeners\BookSavingListener',
        ],
        'App\Events\BookSaved' => [
            'App\Listeners\BookSavedListener',
        ],
        'App\Events\ShudanSaving' => [
            'App\Listeners\ShudanListener',
        ],
        'App\Events\BookShipper' => [
            'App\Listeners\BookShipperListener',
        ],
        'App\Events\ReminderCreated' => [
            'App\Listeners\ReminderCreatedListener',
        ],
        'App\Events\SkuForSale' => [
            'App\Listeners\SkuForSaleListener',
        ],
        'App\Events\GetDouBookInfo' => [
            'App\Listeners\GetDouBookInfoListener',
        ],
        'App\Events\BookRecoverPriceRisen' => [
            'App\Listeners\BookRecoverPriceRisenListener',
        ],
        'App\Events\RecoverOrderChecked' => [
            'App\Listeners\RecoverOrderCheckedListener',
        ],
        'App\Events\OrderItemSaved' => [
            'App\Listeners\OrderItemSavedListener',
        ],
        'App\Events\BookSkuSaved' => [
            'App\Listeners\BookSkuSavedListener',
        ],
        'App\Events\SaleItemSaved' => [
            'App\Listeners\SaleItemSavedListener',
        ],
        'App\Events\CouponSaving' => [
            'App\Listeners\CouponSavingListener',
        ],
        'App\Events\SendCouponEnableMsg' => [
            'App\Listeners\SendCouponEnableMsgListener',
        ],
        'App\Events\BookVersionSaving' => [
            'App\Listeners\BookVersionSavingListener',
        ],
        'App\Events\PictureSaving' => [
            'App\Listeners\PictureSavingListener',
        ],
        'App\Events\BookOnSale' => [
            'App\Listeners\BookOnSaleListener',
        ],
        'App\Events\RecoverReportAccept' => [
            'App\Listeners\RecoverReportAcceptListener',
        ],
        'App\Events\GetUserWechatInfo' => [
            'App\Listeners\GetUserWechatInfoListener',
        ],
        'App\Events\LotterySaving' => [
            'App\Listeners\LotterySavingListener',
        ],
        'App\Events\LotteryOpen' => [
            'App\Listeners\LotteryOpenListener',
        ],
        'App\Events\SendNotifyMessage' => [
            'App\Listeners\SendNotifyMessageListener',
        ],
        'App\Events\NotifyUserHowMany' => [
            'App\Listeners\NotifyUserHowManyListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
