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
            $table->index('group');
            $table->index('service');
            $table->index('method');
            $table->index(['claimed', 'finished', 'priority', 'id']);
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
