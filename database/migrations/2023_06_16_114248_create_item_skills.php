<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_skills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description');
            $table->decimal('str_mod', 12, 8)->nullable()->default(0);
            $table->decimal('dex_mod', 12, 8)->nullable()->default(0);
            $table->decimal('dur_mod', 12, 8)->nullable()->default(0);
            $table->decimal('chr_mod', 12, 8)->nullable()->default(0);
            $table->decimal('focus_mod', 12, 8)->nullable()->default(0);
            $table->decimal('int_mod', 12, 8)->nullable()->default(0);
            $table->decimal('agi_mod', 12, 8)->nullable()->default(0);
            $table->decimal('base_damage_mod', 12, 8)->nullable()->default(0);
            $table->decimal('base_ac_mod', 12, 8)->nullable()->default(0);
            $table->decimal('base_healing_mod', 12, 8)->nullable()->default(0);
            $table->integer('max_level');
            $table->integer('total_kills_needed');
            $table->integer('parent_level_needed')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_skills');
    }
};
