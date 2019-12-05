<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForiegnKeysToItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->bigInteger('item_affix_id')->unsigned()->nullable();
            $table->foreign('item_affix_id')
                ->references('id')->on('item_affixes');
            $table->bigInteger('artifact_property_id')->unsigned()->nullable();
            $table->foreign('artifact_property_id')
                ->references('id')->on('artifact_properties');
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
            $table->dropForeign(['item_affix_id', 'artifact_property_id']);
            $table->dropColumn('item_affix_id');
            $table->dropColumn('artifact_property_i
            ');
        });
    }
}
