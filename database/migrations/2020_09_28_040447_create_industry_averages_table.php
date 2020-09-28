<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndustryAveragesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('industry_averages', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('industry',
                [
                    'apparel',
                    'banking_financial',
                    'electronics',
                    'food_groceries',
                    'goverment',
                    'others'
                ]
            );
            $table->integer('value');
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
        Schema::dropIfExists('industry_averages');
    }
}
