<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidWithGoldToBuildingsInQueue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buildings_in_queue', function (Blueprint $table) {
            $table->boolean('paid_with_gold')->default(false);
            $table->bigInteger('paid_amount')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buildings_in_queue', function (Blueprint $table) {
            $table->dropColumn('paid_with_gold');
            $table->dropColumn('paid_amount');
        });
    }
}
