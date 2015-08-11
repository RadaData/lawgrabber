<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLawsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laws', function(Blueprint $table)
        {
            $table->string('id', 20);
            $table->date('date');
            $table->tinyInteger('status')->nullable();
            $table->string('state', 50)->nullable();
            $table->tinyInteger('has_text')->nullable();
            $table->longText('card')->nullable();
            $table->unsignedInteger('card_updated')->default(0);
            $table->date('active_revision')->nullable();
            $table->primary('id');
            $table->index(['status', 'date'], 'sd');
            $table->index('date', 'd');
        });

        Schema::create('law_revisions', function(Blueprint $table)
        {
            $table->increments('id');
            $table->date('date');
            $table->string('law_id', 20);
            $table->string('state', 50)->nullable();
            $table->longText('text')->nullable();
            $table->unsignedInteger('text_updated')->default(0);
            $table->longText('comment')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->unique(['date', 'law_id'], 'dl');
            $table->index('status', 's');
        });

        Schema::create('issuers', function(Blueprint $table)
        {
            $table->string('id', 10);
            $table->string('name', 255);
            $table->string('full_name', 255)->nullable();
            $table->string('group_name', 255);
            $table->string('website', 255)->nullable();
            $table->string('url', 255);
            $table->tinyInteger('international');
            $table->primary('name');
            $table->index('id', 'i');
        });

        Schema::create('law_issuers', function(Blueprint $table)
        {
            $table->string('law_id', 20);
            $table->string('issuer_name', 255);
            $table->primary(['law_id', 'issuer_name']);
        });

        Schema::create('types', function(Blueprint $table)
        {
            $table->string('id', 20);
            $table->string('name', 255);
            $table->primary('name');
            $table->index('id', 'i');
        });

        Schema::create('law_types', function(Blueprint $table)
        {
            $table->string('law_id', 20);
            $table->string('type_name', 255);
            $table->primary(['law_id', 'type_name']);
        });

        Schema::create('states', function(Blueprint $table)
        {
            $table->string('id', 20);
            $table->string('name', 255);
            $table->primary('name');
            $table->index('id', 'i');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('laws');
        Schema::drop('law_revisions');
        Schema::drop('issuers');
        Schema::drop('law_issuers');
        Schema::drop('types');
        Schema::drop('law_types');
        Schema::drop('states');
    }
}
