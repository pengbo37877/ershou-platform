<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountToBookSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('book_skus', function(Blueprint $table) {
            $table->decimal('discount', 5, 2)->nullable()->comment('图书折扣');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('book_skus', function(Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
}
