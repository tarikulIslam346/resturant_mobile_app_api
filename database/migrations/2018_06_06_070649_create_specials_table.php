<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('specials', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rest_id');
            $table->string('code');
            $table->string('title');
            $table->string('description')->nullable();
            $table->double('price');
            $table->double('discount')->nullable();
            $table->string('for')->nullable();
            $table->string('available')->nullable();
            $table->integer('click_count')->nullable();
            $table->string('image');
            $table->boolean('status');
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
        Schema::dropIfExists('specials');
    }
}
