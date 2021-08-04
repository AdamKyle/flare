<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewStatsToItemsAndItemAffixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('agi_mod', 5, 4)->nullable();
            $table->decimal('focus_mod', 5, 4)->nullable();
        });

        Schema::table('item_affixes', function (Blueprint $table) {
            $table->decimal('agi_mod', 5, 4)->nullable();
            $table->decimal('focus_mod', 5, 4)->nullable();
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
            $table->dropColumn('agi_mod', 5, 4);
            $table->dropColumn('focus_mod', 5, 4);
        });

        Schema::table('item_affixes', function (Blueprint $table) {
            $table->dropColumn('agi_mod', 5, 4);
            $table->dropColumn('focus_mod', 5, 4);
        });
    }
}
