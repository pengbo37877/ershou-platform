<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterBookSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('book_skus', function (Blueprint $table) {
            $table->integer("soldtime")->nullable()->comment("流转时间(秒)");
            $table->timestamp("retreading_at")->nullable()->comment("翻新时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('book_skus', function (Blueprint $table) {
            //
        });
    }
}
