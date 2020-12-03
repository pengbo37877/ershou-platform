<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('avatar')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('access_token')->nullable();
            $table->integer('expires_in')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('mp_open_id')->nullable();
            $table->string('xcx_open_id')->nullable();
            $table->string('union_id');
            $table->string('phone')->nullable();
            $table->tinyInteger('subscribe')->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
