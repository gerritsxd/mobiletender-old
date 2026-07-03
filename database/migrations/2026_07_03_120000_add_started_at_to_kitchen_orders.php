<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartedAtToKitchenOrders extends Migration
{
    public function up()
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('sent_at');
        });
    }

    public function down()
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });
    }
}
