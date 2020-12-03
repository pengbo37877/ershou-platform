<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDouListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dou_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('doulist_id');
            $table->string('name');
            $table->text('desc')->nullable();
            $table->unsignedInteger('start')->default(0);
            $table->unsignedInteger('book_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->unsignedInteger('recommend_count')->default(0);
            $table->text('subjectids')->nullable();
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
        Schema::dropIfExists('dou_lists');
    }
}
