<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRestaurantOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->increments('id');
            //$table->string('order_id');
            $table->integer('order_by');
            $table->integer('order_for');
            $table->double('total')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('received_by')->nullable();
            $table->string('approximate_time')->nullable();
            $table->string('status');
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
        Schema::dropIfExists('restaurant_orders');
    }
}
