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
        Schema::create('gems', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('primary_atonement_type');
            $table->integer('secondary_atonement_type');
            $table->integer('tertiary_atonement_type');
            $table->decimal('primary_atonement_amount', 12, 8);
            $table->decimal('secondary_atonement_amount', 12, 8);
            $table->decimal('tertiary_atonement_amount', 12, 8);
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
        Schema::dropIfExists('gems');
    }
};
