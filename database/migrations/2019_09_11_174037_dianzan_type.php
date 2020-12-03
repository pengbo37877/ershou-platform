<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DianzanType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shudan_dianzan', function($table) {
            $table->tinyInteger('type')->default(1)->comment('1评论  2订单');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shudan_dianzan', function($table) {
            $table->dropColumn('type');
        });
    }
}
