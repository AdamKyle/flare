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
        Schema::table('kingdom_logs', function (Blueprint $table) {
            $table->decimal('morale_loss', 5, 4)->change();
            $table->decimal('item_damage', 5, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kingdom_logs', function (Blueprint $table) {
            // do not revert
        });
    }
};
