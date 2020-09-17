<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackVulnerabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback_vulnerabilities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('feedback_id')->unsigned();
            $table->foreign('feedback_id')
                ->references('id')
                ->on('feedback')->onDelete('cascade');
            $table->integer('vulnerability_id')->unsigned();
            $table->foreign('vulnerability_id')
                ->references('id')
                ->on('vulnerabilities')->onDelete('cascade');
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
        Schema::dropIfExists('feedback_vulnerabilities');
    }
}
