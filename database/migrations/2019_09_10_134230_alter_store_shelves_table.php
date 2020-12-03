<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStoreShelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_shelves', function (Blueprint $table) {
            $table->string('shelf_num')->nullable()->comment("书架编号");
            $table->string('row_num')->nullable()->comment("排编号");
            $table->string('floor_num')->nullable()->comment("层数");
            $table->string('box_num')->nullable()->comment("格子编号");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('store_shelves', function (Blueprint $table) {
            //
        });
    }
}
