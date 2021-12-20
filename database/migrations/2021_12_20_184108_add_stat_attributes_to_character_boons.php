<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatAttributesToCharacterBoons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('character_boons', function (Blueprint $table) {
            $table->decimal('str_mod', 8, 4)->nullable()->default(0);
            $table->decimal('dex_mod', 8, 4)->nullable()->default(0);
            $table->decimal('dur_mod', 8, 4)->nullable()->default(0);
            $table->decimal('int_mod', 8, 4)->nullable()->default(0);
            $table->decimal('chr_mod', 8, 4)->nullable()->default(0);
            $table->decimal('focus_mod', 8, 4)->nullable()->default(0);
            $table->decimal('agi_mod', 8, 4)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('character_boons', function (Blueprint $table) {
            $table->dropColumn('str_mod');
            $table->dropColumn('dex_mod');
            $table->dropColumn('dex_mod');
            $table->dropColumn('int_mod');
            $table->dropColumn('chr_mod');
            $table->dropColumn('focus_mod');
            $table->dropColumn('agi_mod');
        });
    }
}
