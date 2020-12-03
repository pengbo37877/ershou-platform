<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShudanDianzanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shudan_dianzan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id')->comment('评论id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->tinyInteger('status')->default(1)->comment('点赞状态，0取消点赞');
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
        Schema::dropIfExists('shudan_dianzan');
    }
}
