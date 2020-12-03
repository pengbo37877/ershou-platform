<?php

namespace App\Observers;


use App\BookSku;

class BookSkuObserver
{
    /**
     * 监听数据即将创建的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function creating(BookSku $sku)
    {

    }

    /**
     * 监听数据创建后的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function created(BookSku $sku)
    {

    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function updating(BookSku $sku)
    {

    }

    /**
     * 监听数据更新后的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function updated(BookSku $sku)
    {

    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function saving(BookSku $sku)
    {

    }

    /**
     * 监听数据保存后的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function saved(BookSku $sku)
    {
        $book = $sku->book;
        $book->sale_sku_count = BookSku::where('book_id', $sku->book_id)->where('status', BookSku::STATUS_FOR_SALE)->count();
        $book->save();
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function deleting(BookSku $sku)
    {

    }

    /**
     * 监听数据删除后的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function deleted(BookSku $sku)
    {

    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function restoring(BookSku $sku)
    {

    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param  BookSku $sku
     * @return void
     */
    public function restored(BookSku $sku)
    {

    }
}