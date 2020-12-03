<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->unsignedInteger('book_id');
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->unsignedInteger('book_sku_id')->nullable();
            $table->foreign('book_sku_id')->references('id')->on('book_skus')->onDelete('cascade');
            $table->unsignedInteger('amount')->default(1); // 数量(默认是1)
            $table->decimal('price', 10, 2); // 价格
            $table->unsignedInteger('rating')->nullable(); // 评分
            $table->boolean('review_result')->default(1); // 审核结果
            $table->boolean('is_add')->default(0); // 运营添加的
            $table->text('review')->nullable(); // 复审这个和进销存有关系
            $table->timestamp('reviewed_at')->nullable();
            $table->string('source')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
