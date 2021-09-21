<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDamageToItemAffixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->bigInteger('damage')->nullable()->default(0);
            $table->boolean('damage_can_stack')->default(false);
            $table->boolean('irresistible_damage')->default(false);
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
            $table->dropColumn('damage');
            $table->dropColumn('damage_can_stack');
            $table->dropColumn('irresistible_damage');
        });
    }
}
