<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOverFlowSetIdToAdventures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adventure_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('over_flow_set_id')->nullable();
            $table->foreign('over_flow_set_id')
                  ->references('id')->on('inventory_sets');
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
            $table->dropForeign(['over_flow_set_id']);
            $table->dropColumn('over_flow_set_id');
        });
    }
}
