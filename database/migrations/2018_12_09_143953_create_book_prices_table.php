<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBookPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_prices', function (Blueprint $table) {
            $table->string('isbn');
            $table->decimal('dzy_price')->nullable(); // dzy旧书价格
            $table->decimal('dzy_new_price')->nullable(); // dzy新书价格
            $table->decimal('dd_new_price')->nullable(); // 当当网
            $table->decimal('amz_new_price')->nullable(); // z.cn
            $table->decimal('jd_new_price')->nullable(); // jd.com
            $table->decimal('bc_new_price')->nullable(); // bookschina
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
        Schema::dropIfExists('book_prices');
    }
}
