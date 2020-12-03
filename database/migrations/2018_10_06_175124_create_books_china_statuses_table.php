<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBooksChinaStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books_china_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('start_id');
            $table->unsignedInteger('current_id');
            $table->unsignedInteger('end_id');
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
        Schema::dropIfExists('books_china_statuses');
    }
}
