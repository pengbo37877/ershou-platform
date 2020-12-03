<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->increments('id');
            $table->string('isbn');
            $table->string('name')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('original_name')->nullable();
            $table->string('author')->nullable();
            $table->string('translator')->nullable();
            $table->string('press')->nullable();
            $table->string('publish_year')->nullable();
            $table->string('page_num')->nullable();
            $table->string('price')->nullable();
            $table->string('binding')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('rating_num')->nullable();
            $table->string('dangdang_url')->nullable();
            $table->string('jd_url')->nullable();
            $table->string('amazon_url')->nullable();
            $table->string('douban_url')->nullable();
            $table->text('author_intro')->nullable();
            $table->text('summary')->nullable();
            $table->tinyInteger('type')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('books');
    }
}
