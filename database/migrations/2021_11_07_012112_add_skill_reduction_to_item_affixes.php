<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkillReductionToItemAffixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->decimal('skill_reduction', 8, 4)->nullable()->default(0);
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
            $table->dropColumn('skill_reduction');
        });
    }
}
