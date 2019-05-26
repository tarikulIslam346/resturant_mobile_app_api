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
            $table->string('client_id')->unique()->nullable();
            $table->string('rest_id')->nullable();
            $table->string('type')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('contact')->nullable();
            $table->string('gender')->nullable();
            $table->string('dob')->nullable();
            $table->string('password');
            $table->boolean('status')->nullable();
            $table->string('profile_pic')->nullable();
            $table->string('verification_code')->nullable();
            $table->rememberToken()->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
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
