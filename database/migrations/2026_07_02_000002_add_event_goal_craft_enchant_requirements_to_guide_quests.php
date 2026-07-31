<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->unsignedBigInteger('required_event_goal_crafting_participation')->nullable()->after('required_event_goal_participation');
            $table->unsignedBigInteger('required_event_goal_enchanting_participation')->nullable()->after('required_event_goal_crafting_participation');
        });
    }

    public function down(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->dropColumn([
                'required_event_goal_crafting_participation',
                'required_event_goal_enchanting_participation',
            ]);
        });
    }
};
