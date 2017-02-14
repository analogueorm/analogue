<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url');
            $table->integer('size_width')->nullable();
            $table->integer('size_height')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('custom_width')->nullable();
            $table->integer('custom_height')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->json('size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('images');
    }
}
