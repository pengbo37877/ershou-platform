<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_skus', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->default(0);
            $table->unsignedInteger('from_order')->default(0);
            $table->unsignedInteger('to_order')->default(0);
            $table->string('title');
            $table->string('description');
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2); // 原书价格换算成人民币价格
            $table->decimal('recover_price', 10, 2); // 回收价
            $table->unsignedInteger('level')->default(0); // 品相 99：99新；80：中等；60：有痕迹；0：未分类
            $table->unsignedInteger('book_id');
            $table->string('isbn');
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->unsignedInteger('status'); // 0: 未上架， 1：已上架，2：拒绝上架， 3：已退回，4：已售卖(在用户手中)
            $table->string('hly_code'); // 回流鱼为每一本书生成的唯一编码
            $table->string('groups'); // 分组
            $table->string('mark'); // 备注
            $table->unsignedInteger('store_shelf_id')->nullable(); // 仓库ID
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
        Schema::dropIfExists('book_skus');
    }
}
