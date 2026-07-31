<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->json('required_batch_crafted_items')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->dropColumn('required_batch_crafted_items');
        });
    }
};
