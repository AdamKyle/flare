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
        Schema::table('kingdom_logs', function (Blueprint $table) {
            $table->bigInteger('attacking_character_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kingdom_logs', function (Blueprint $table) {
            $table->dropColumn('attacking_character_id');
        });
    }
};
