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
            $table->integer('required_kingdoms')->nullable();
            $table->integer('required_kingdom_level')->nullable();
            $table->integer('required_kingdom_units')->nullable();
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
            $table->dropColumn('required_kingdoms');
            $table->dropColumn('required_kingdom_level');
            $table->dropColumn('required_kingdom_units');
        });
    }
};
