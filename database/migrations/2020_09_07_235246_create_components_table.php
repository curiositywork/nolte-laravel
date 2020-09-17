<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('components', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('slug', 20);
            $table->enum('component_type', ['plugin', 'theme', 'wordpress']);
            $table->boolean('closed')->default(false);
            $table->boolean('popular')->default(false);
            $table->mediumText('closed_reason')->nullable();
            $table->string('latest_version', 20)->nullable();
            $table->string('friendly_name', 50)->nullable();
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
        Schema::dropIfExists('components');
    }
}
