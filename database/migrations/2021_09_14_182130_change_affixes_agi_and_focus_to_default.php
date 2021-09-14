<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAffixesAgiAndFocusToDefault extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->decimal('agi_mod', 5, 4)->nullable()->default(0.0)->change();
            $table->decimal('focus_mod', 5, 4)->nullable()->default(0.0)->change();
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
            $table->decimal('agi_mod', 5, 4)->nullable()->default(null)->change();
            $table->decimal('focus_mod', 5, 4)->nullable()->default(null)->change();
        });
    }
}
