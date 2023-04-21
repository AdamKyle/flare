<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->bigInteger('gold_dust_reward')->nullable();
            $table->bigInteger('shards_reward')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->dropColumn('gold_dust_reward');
            $table->dropColumn('shards_reward');
        });
    }
};
