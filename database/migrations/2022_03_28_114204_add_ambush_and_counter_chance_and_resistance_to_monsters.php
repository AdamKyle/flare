<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmbushAndCounterChanceAndResistanceToMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->decimal('ambush_chance', 8, 4)->nullable()->default(0.0);
            $table->decimal('ambush_resistance', 8, 4)->nullable()->default(0.0);
            $table->decimal('counter_chance', 8, 4)->nullable()->default(0.0);
            $table->decimal('counter_resistance', 8, 4)->nullable()->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->decimal('ambush_chance', 8, 4)->nullable()->default(0.0);
            $table->decimal('ambush_resistance', 8, 4)->nullable()->default(0.0);
            $table->decimal('counter_chance', 8, 4)->nullable()->default(0.0);
            $table->decimal('counter_resistance', 8, 4)->nullable()->default(0.0);
        });
    }
}
