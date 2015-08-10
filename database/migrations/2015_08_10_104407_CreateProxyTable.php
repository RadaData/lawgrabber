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
            $table->unsignedInteger('last_used');
            $table->tinyInteger('in_use');
            $table->unique('address');
            $table->unique('ip');
            $table->index(['in_use', 'last_used']);
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
