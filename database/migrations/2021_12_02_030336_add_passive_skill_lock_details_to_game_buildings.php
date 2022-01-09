<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassiveSkillLockDetailsToGameBuildings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_buildings', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('passive_skill_id')->nullable();
            $table->foreign('passive_skill_id', 'gb_psk')
                  ->references('id')->on('passive_skills');
            $table->integer('level_required')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_buildings', function (Blueprint $table) {
            $table->dropForeign(['passive_skill_id']);
            $table->dropColumn('is_locked');
            $table->dropColumn('passive_skill_id');
            $table->dropColumn('level_required');
        });
    }
}
