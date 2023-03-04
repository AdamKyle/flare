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
        Schema::table('game_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('primary_required_class_id')->nullable();
            $table->unsignedBigInteger('secondary_required_class_id')->nullable();
            $table->integer('primary_required_class_level')->nullable();
            $table->integer('secondary_required_class_level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_classes', function (Blueprint $table) {
            $table->dropColumn('primary_required_class_id');
            $table->dropColumn('secondary_required_class_id');
            $table->dropColumn('primary_required_class_level');
            $table->dropColumn('secondary_required_class_level');
        });
    }
};
