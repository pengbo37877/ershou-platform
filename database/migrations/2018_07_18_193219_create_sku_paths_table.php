<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSkuPathsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sku_paths', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('book_sku_id');
            $table->foreign('book_sku_id')->references('id')->on('book_skus')->onDelete('cascade');
            $table->unsignedInteger('prev_user_id')->default(0);
            $table->unsignedInteger('user_id');
            $table->boolean('is_owner')->default(false);
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
        Schema::dropIfExists('sku_paths');
    }
}
