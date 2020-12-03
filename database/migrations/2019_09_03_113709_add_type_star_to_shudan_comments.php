<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeStarToShudanComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shudan_comments', function($table) {
            $table->tinyInteger('star')->default(0)->comment('评分');
            $table->tinyInteger('type')->default(1)->comment('1书单 2评论');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shudan_comments', function($table) {
            $table->dropColumn('star');
            $table->dropColumn('type');
        });
    }
}
