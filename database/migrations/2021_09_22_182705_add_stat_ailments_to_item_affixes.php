<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatAilmentsToItemAffixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->decimal('str_reduction', 5, 4)->nullable()->default(0);
            $table->decimal('dur_reduction', 5, 4)->nullable()->default(0);
            $table->decimal('dex_reduction', 5, 4)->nullable()->default(0);
            $table->decimal('chr_reduction', 5, 4)->nullable()->default(0);
            $table->decimal('int_reduction', 5, 4)->nullable()->default(0);
            $table->decimal('agi_reduction', 5, 4)->nullable()->default(0);
            $table->decimal('focus_reduction', 5, 4)->nullable()->default(0);
            $table->decimal('steal_life_amount', 5, 4)->nullable()->defualt(0);
            $table->decimal('entranced_chance', 5, 4)->nullable()->default(0);
            $table->boolean('reduces_enemy_stats')->default(false);
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
            $table->dropColumn('str_reduction');
            $table->dropColumn('dur_reduction');
            $table->dropColumn('dex_reduction');
            $table->dropColumn('chr_reduction');
            $table->dropColumn('int_reduction');
            $table->dropColumn('agi_reduction');
            $table->dropColumn('focus_reduction');
            $table->dropColumn('steal_life_amount');
            $table->dropColumn('entranced_chance');
            $table->dropColumn('reduces_enemy_stats');
        });
    }
}
