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
            $table->date('date')->nullable();
            $table->text('title')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('state', 50)->nullable();
            $table->tinyInteger('has_text')->default(0);
            $table->longText('card')->nullable();
            $table->unsignedInteger('card_updated')->default(0);
            $table->date('active_revision')->nullable();
            $table->primary('id');
            $table->index(['status', 'date'], 'laws_sd');
            $table->index('date', 'laws_d');
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
            $table->tinyInteger('status')->default(0);
            $table->unique(['date', 'law_id'], 'law_revisions_dl');
            $table->index('status', 'law_revisions_s');
            $table->index('law_id', 'law_revisions_l');
        });

        Schema::create('issuers', function(Blueprint $table)
        {
            $table->string('id', 10);
            $table->string('name', 255);
            $table->string('full_name', 255)->nullable();
            $table->string('group_name', 255)->nullable();;
            $table->string('website', 255)->nullable();
            $table->string('url', 255)->nullable();;
            $table->tinyInteger('international')->default(0);
            $table->primary('name');
            $table->index('id', 'issuers_i');
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
            $table->index('id', 'types_i');
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
            $table->index('id', 'states_i');
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
