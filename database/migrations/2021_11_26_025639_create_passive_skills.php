<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassiveSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('passive_skills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description');
            $table->integer('max_level');
            $table->decimal('bonus_per_level', 8, 4);
            $table->integer('effect_type');
            $table->bigInteger('parent_skill_id')->nullable()->unisgned();
            $table->integer('unlocks_at_level')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_parent')->default(false);
            $table->integer('hours_per_level');
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
        Schema::dropIfExists('passive_skills');
    }
}
