<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreShelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_shelves', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code'); // 编码
            $table->string('desc'); // 说明
            $table->unsignedInteger('capacity'); // 容量
            $table->string('unit'); // 容量单位：个数，体积
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
        Schema::dropIfExists('store_shelves');
    }
}
