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
        Schema::table('items', function (Blueprint $table) {
            $table->bigInteger('gold_bars_cost')->nullable();
            $table->boolean('can_stack')->default(false);
            $table->boolean('gains_additional_level')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('gold_bars_cost');
            $table->dropColumn('can_stack');
            $table->dropColumn('gains_additional_level');
        });
    }
};
