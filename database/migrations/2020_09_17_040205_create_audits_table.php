<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 100);
            $table->mediumText('title');
            $table->string('display_value', 100)->nullable();
            $table->string('display_mode', 100)->nullable();
            $table->float('score', 4, 2)->nullable();
            $table->double('numeric_value', 8, 4)->nullable();
            $table->integer('insight_id')->unsigned();
            $table->foreign('insight_id')->references('id')->on('insights')->onDelete('cascade');
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
        Schema::dropIfExists('audits');
    }
}
