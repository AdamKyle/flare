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
            $table->dropColumn('old_defender');
            $table->dropColumn('new_defender');

            $table->json('old_buildings');
            $table->json('new_buildings');
            $table->json('old_units');
            $table->json('new_units');
            $table->decimal('morale_loss', 8, 8);
            $table->decimal('item_damage', 8, 8)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // do not go back.
    }
};
