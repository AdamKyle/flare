<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChatSettingsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_unit_recruitment_messages')->default(false);
            $table->boolean('show_building_upgrade_messages')->default(false);
            $table->boolean('show_building_rebuilt_messages')->default(false);
            $table->boolean('show_kingdom_update_messages')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('show_unit_recruitment_messages');
            $table->dropColumn('show_building_upgrade_messages');
            $table->dropColumn('show_building_rebuilt_messages');
            $table->dropColumn('show_kingdom_update_messages');
        });
    }
}
