<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBooksChinasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books_chinas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('books_china_id');
            $table->string('name');
            $table->string('author');
            $table->string('press');
            $table->string('series');
            $table->string('price');
            $table->string('isbn');
            $table->string('binding');
            $table->string('category');
            $table->text('summary');
            $table->text('author_intro');
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
        Schema::dropIfExists('books_chinas');
    }
}
