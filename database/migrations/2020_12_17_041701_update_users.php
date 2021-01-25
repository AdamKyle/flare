<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('adventure_email')->nullable()->default(true);
            $table->boolean('new_building_email')->nullabel()->default(true);
            $table->boolean('upgraded_building_email')->nullable()->default(true);
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
            $table->dropColumn('adventure_email');
            $table->dropColumn('new_building_email');
            $table->dropColumn('upgraded_building_email');
        });
    }
}
