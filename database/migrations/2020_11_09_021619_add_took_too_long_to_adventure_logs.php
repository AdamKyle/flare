<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTookTooLongToAdventureLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adventure_logs', function (Blueprint $table) {
            $table->boolean('took_to_long')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adventure_logs', function (Blueprint $table) {
            $table->dropColumn('took_to_long');
        });
    }
}
