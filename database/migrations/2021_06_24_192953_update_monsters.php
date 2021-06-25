<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->boolean('can_cast')->default(false);
            $table->boolean('can_use_artifacts')->default(false);
            $table->integer('max_spell_damage')->nullable()->default(0);
            $table->integer('max_artifact_damage')->nullable()->default(0);
            $table->integer('shards')->nullable()->default(0);
            $table->decimal('spell_evasion', 8, 4)->nullable()->default(0);
            $table->decimal('artifact_annulment', 8, 4)->nullable()->default(0);
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
            $table->dropColumn('can_cast');
            $table->dropColumn('can_use_artifacts');
            $table->dropColumn('max_spell_damage');
            $table->dropColumn('max_artifact_damage');
            $table->dropColumn('shards');
            $table->dropColumn('spell_evasion');
            $table->dropColumn('artifact_annulment');
        });
    }
}
