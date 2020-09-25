<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdventureIdToNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->bigInteger('adventure_id')->unsigned()->nullable();
            $table->foreign('adventure_id')
                  ->references('id')->on('adventures');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign('notifications_adventure_id_foreign');
            $table->dropColumn('adventure_id');
        });
    }
}
