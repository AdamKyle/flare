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
        Schema::table('faction_loyalty_automation_warnings', function (Blueprint $table) {
            $table->string('log_entry_id')->nullable()->after('log_type');
            $table->index('log_entry_id', 'fl_automation_warnings_log_entry_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faction_loyalty_automation_warnings', function (Blueprint $table) {
            $table->dropIndex('fl_automation_warnings_log_entry_id_index');
            $table->dropColumn('log_entry_id');
        });
    }
};
