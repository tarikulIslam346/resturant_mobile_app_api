<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRestaurantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->increments('id');
            $table->string('factual_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('category')->nullable();
            $table->string('address')->nullable();
            $table->string('postcode')->nullable();
            $table->string('locality')->nullable();
            $table->string('region')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('web')->nullable();
            $table->string('rating')->nullable();
            $table->string('category_labels')->nullable();
            $table->string('cuisine')->nullable();
            $table->string('opening')->nullable();
            $table->string('closing')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
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
        Schema::dropIfExists('restaurants');
    }
}
