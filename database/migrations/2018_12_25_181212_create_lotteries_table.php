<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLotteriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lotteries', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('start_at'); // 开始时间
            $table->timestamp('end_at')->nullable(); // 结束时间
            $table->string('title'); // 标题
            $table->string('sub_title')->nullable(); // 副标题
            $table->text('body'); // 图文介绍
            $table->unsignedTinyInteger('winner_count'); // 获奖人数
            $table->unsignedTinyInteger('end_count')->default(0); // 结束人数
            $table->unsignedTinyInteger('type'); // 类型
            $table->unsignedTinyInteger('status'); // 状态
            $table->unsignedInteger('participants_count')->default(0); // 参与人数【冗余字段】
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
        Schema::dropIfExists('lotteries');
    }
}
