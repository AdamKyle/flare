<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCanDropToItemAffixes extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->boolean('can_drop')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->dropColumn('can_drop');
        });
    }
}
