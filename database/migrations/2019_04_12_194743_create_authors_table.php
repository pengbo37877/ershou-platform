<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('author_id');
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->string('gender')->nullable();
            $table->string('live_day')->nullable();
            $table->string('country')->nullable();
            $table->string('en_name')->nullable();
            $table->string('cn_name')->nullable();
            $table->text('intro')->nullable();
            $table->text('hot_books')->nullable();
            $table->unsignedInteger('hot_start')->default(0);
            $table->text('new_books')->nullable();
            $table->unsignedInteger('new_start')->default(0);
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
        Schema::dropIfExists('authors');
    }
}
