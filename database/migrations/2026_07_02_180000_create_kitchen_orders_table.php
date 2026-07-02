<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKitchenOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('kitchen_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('table_number');
            $table->string('ordered_by')->nullable();
            $table->string('status')->default('pending'); // pending | ready | delivered
            $table->dateTime('sent_at');
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'sent_at']);
        });

        Schema::create('kitchen_order_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kitchen_order_id')->index();
            $table->string('product_id')->nullable();
            $table->string('product_name');
            $table->double('price')->default(0);
            $table->string('printto')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kitchen_order_lines');
        Schema::dropIfExists('kitchen_orders');
    }
}
