<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookSnapshotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('book_id');
            $table->string('isbn');
            $table->string('rating_num')->nullable(); // 豆瓣评分
            $table->string('num_raters')->nullable(); // 评分人数
            $table->integer('reminder_count')->nullable(); // 到货提醒人数
            $table->integer('all_sku_count')->nullable(); // 在售sku个数
            $table->integer('sale_sku_count')->nullable(); // 在售sku个数
            $table->integer('cart_item_count')->nullable(); // 加入购物车人数
            $table->integer('discount')->nullable(); // 收购折扣
            $table->decimal('avg_sale_price')->nullable(); // 正在售卖的均价
            $table->tinyInteger('can_recover')->nullable(); // 是否收取
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('book_snapshots');
    }
}
