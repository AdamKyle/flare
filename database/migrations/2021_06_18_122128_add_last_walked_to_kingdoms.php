<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastWalkedToKingdoms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->timestamp('last_walked')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->dropColumn('last_walked');
        });
    }
}
