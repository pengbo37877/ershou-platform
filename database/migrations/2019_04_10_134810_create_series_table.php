<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('series', function (Blueprint $table) {
            $table->increments('id');
            $table->string('series_id');
            $table->string('name');
            $table->string('press');
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('page')->default(1);
            $table->text('desc')->nullable();
            $table->unsignedInteger('recommend_count')->default(0);
            $table->text('subjectids');
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
        Schema::dropIfExists('series');
    }
}
