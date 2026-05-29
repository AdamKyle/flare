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
        Schema::table('faction_loyalty_automations', function (Blueprint $table) {
            $table->renameColumn('faction_loyalty_npc_task_id', 'faction_loyalty_npc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faction_loyalty_automations', function (Blueprint $table) {
            $table->renameColumn('faction_loyalty_npc_id', 'faction_loyalty_npc_task_id');
        });
    }
};
