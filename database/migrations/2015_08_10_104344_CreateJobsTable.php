<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('service', 255);
            $table->string('method', 100);
            $table->text('parameters')->nullable();
            $table->string('group', 100)->nullable();
            $table->unsignedInteger('claimed')->default(0);
            $table->unsignedInteger('finished')->default(0);
            $table->tinyInteger('priority')->default(0);
            $table->index('group', 'g');
            $table->index('service', 's');
            $table->index(['claimed', 'finished', 'priority', 'id'], 'cfpi');
            $table->index(['finished', 'method'], 'fm');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('jobs');
    }
}
