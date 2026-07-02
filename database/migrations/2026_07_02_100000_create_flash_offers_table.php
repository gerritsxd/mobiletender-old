<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlashOffersTable extends Migration
{
    public function up()
    {
        Schema::create('flash_offers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->string('message')->nullable();
            $table->string('product_id')->nullable();
            $table->double('flash_price')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->index(['active', 'starts_at', 'ends_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('flash_offers');
    }
}
