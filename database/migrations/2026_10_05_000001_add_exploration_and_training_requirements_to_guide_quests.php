<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->unsignedInteger('required_exploration_hours')->nullable();
            $table->unsignedBigInteger('required_training_skill')->nullable();
            $table->decimal('required_training_xp_sacrifice', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->dropColumn([
                'required_exploration_hours',
                'required_training_skill',
                'required_training_xp_sacrifice',
            ]);
        });
    }
};
