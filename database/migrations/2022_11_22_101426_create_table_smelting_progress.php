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
        Schema::create('smelting_progress', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('character_id');
            $table->bigInteger('kingdom_id');
            $table->dateTime('started_at');
            $table->dateTime('completed_at');
            $table->bigInteger('amount_to_smelt');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('smelting_progress');
    }
};
