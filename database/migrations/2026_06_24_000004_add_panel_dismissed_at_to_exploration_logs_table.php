<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exploration_logs', function (Blueprint $table): void {
            $table->timestamp('panel_dismissed_at')->nullable()->after('ended_at');
            $table->index(['character_id', 'panel_dismissed_at'], 'exploration_logs_character_dismissed_index');
        });
    }

    public function down(): void
    {
        Schema::table('exploration_logs', function (Blueprint $table): void {
            $table->dropIndex('exploration_logs_character_dismissed_index');
            $table->dropColumn('panel_dismissed_at');
        });
    }
};
