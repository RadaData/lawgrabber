<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProxyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proxy', function(Blueprint $table)
        {
            $table->string('address', 50);
            $table->string('ip', 20);
            $table->unsignedInteger('last_used')->default(0);
            $table->tinyInteger('in_use')->default(0);
            $table->primary(['address', 'ip']);
            $table->index(['in_use', 'last_used'], 'il');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('proxy');
    }
}
