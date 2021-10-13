<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkillPrecentagesToMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->decimal('accuracy', 8, 4)->nullable()->default(0);
            $table->decimal('casting_accuracy', 8, 4)->nullable()->default(0);
            $table->decimal('dodge', 8, 4)->nullable()->default(0);
            $table->decimal('criticality', 8, 4)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->dropColumn('accuracy');
            $table->dropColumn('casting_accuracy');
            $table->dropColumn('dodge');
            $table->dropColumn('criticality');
        });
    }
}
