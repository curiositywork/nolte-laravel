<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url', 100);
            $table->enum('business_type', ['digital', 'ecommerce', 'both']);
            $table->enum('size', ['micro', 'small', 'medium', 'large']);
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
        Schema::dropIfExists('companies');
    }
}
