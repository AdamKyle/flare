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
        Schema::create('faction_loyalty_automation_warnings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('faction_loyalty_automation_id');
            $table->unsignedBigInteger('faction_loyalty_automation_log_id')->nullable();
            $table->unsignedBigInteger('faction_loyalty_npc_id')->nullable();
            $table->string('log_type')->nullable();
            $table->string('log_entry_id')->nullable();
            $table->string('type');
            $table->text('message');
            $table->timestamps();

            $table->index(['character_id', 'id'], 'fl_automation_warnings_character_id_id_index');
            $table->index('faction_loyalty_automation_id', 'fl_automation_warnings_automation_id_index');
            $table->index('faction_loyalty_automation_log_id', 'fl_automation_warnings_log_id_index');
            $table->index('log_entry_id', 'fl_automation_warnings_log_entry_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faction_loyalty_automation_warnings');
    }
};
