<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exploration_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('character_automation_id');
            $table->unsignedBigInteger('monster_id');
            $table->string('attack_type');

            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->string('stopped_reason')->nullable();
            $table->boolean('stopped_by_player')->default(false);

            $table->unsignedInteger('fights')->default(0);
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedBigInteger('weapon_damage')->default(0);
            $table->unsignedBigInteger('spell_damage')->default(0);
            $table->unsignedBigInteger('xp_gained')->default(0);
            $table->unsignedBigInteger('skill_xp_gained')->default(0);
            $table->unsignedBigInteger('faction_points_gained')->default(0);

            $table->json('currencies_gained')->nullable();
            $table->json('summary')->nullable();

            $table->timestamps();

            $table->index('character_id');
            $table->index('character_automation_id');
            $table->index(['character_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exploration_logs');
    }
};
