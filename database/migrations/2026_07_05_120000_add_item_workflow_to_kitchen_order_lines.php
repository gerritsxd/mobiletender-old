<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddItemWorkflowToKitchenOrderLines extends Migration
{
    public function up()
    {
        Schema::table('kitchen_order_lines', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->after('product_name');
            $table->string('status')->default('pending')->after('printto'); // pending|preparing|ready|delivered
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('kitchen_order_lines', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'status', 'started_at', 'ready_at', 'delivered_at']);
        });
    }
}
