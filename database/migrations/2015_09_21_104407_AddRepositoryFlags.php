<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRepositoryFlags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('law_revisions', function (Blueprint $table) {
            $table->boolean('r_zakon');
            $table->index('r_zakon', 'rz');
            $table->boolean('r_zakon-markdown');
            $table->index('r_zakon-markdown', 'rzm');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('law_revisions', function (Blueprint $table) {
            $table->dropColumn(['r_zakon', 'r_zakon-markdown']);
        });
    }
}
