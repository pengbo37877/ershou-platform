<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJuzisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('juzis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('picture_id')->nullable();
            $table->text('body'); // 句子内容
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('book_id')->nullable();
            $table->string('author')->nullable(); // 句子的作者
            $table->string('book')->nullable(); // 句子的出处
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
        Schema::dropIfExists('juzis');
    }
}
