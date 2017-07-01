<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCustomUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('custom_groups', function (Blueprint $table) {
            $table->increments('groupid');
            $table->string('name');
        });
        Schema::create('custom_user_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('userid');
            $table->integer('groupid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('vehicles');
    }
}
