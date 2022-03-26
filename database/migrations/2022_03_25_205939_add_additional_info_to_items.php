<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalInfoToItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('ambush_chance', 8, 4)->nullable()->default(0.0);
            $table->decimal('ambush_resistance', 8, 4)->nullable()->default(0.0);
            $table->decimal('counter_chance', 8, 4)->nullable()->default(0.0);
            $table->decimal('counter_resistance', 8, 4)->nullable()->default(0.0);
            $table->integer('copper_coin_cost')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('ambush_chance');
            $table->dropColumn('ambush_resistance');
            $table->dropColumn('counter_chance');
            $table->dropColumn('counter_resistance');
            $table->dropColumn('copper_coin_cost');

        });
    }
}
