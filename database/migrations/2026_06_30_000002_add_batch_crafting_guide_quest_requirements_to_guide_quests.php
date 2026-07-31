<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->string('required_batch_crafting_type')->nullable();
            $table->unsignedInteger('required_batch_crafting_hours')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->dropColumn([
                'required_batch_crafting_type',
                'required_batch_crafting_hours',
            ]);
        });
    }
};
