<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exploration_warnings', function (Blueprint $table): void {
            $table->timestamp('dismissed_at')->nullable()->after('message');
            $table->index(['character_id', 'dismissed_at'], 'exploration_warnings_character_dismissed_index');
        });

        Schema::table('delve_explorations', function (Blueprint $table): void {
            $table->string('ended_reason')->nullable()->after('completed_at');
            $table->timestamp('panel_dismissed_at')->nullable()->after('ended_reason');
            $table->index(['character_id', 'panel_dismissed_at'], 'delve_explorations_character_dismissed_index');
        });
    }

    public function down(): void
    {
        Schema::table('exploration_warnings', function (Blueprint $table): void {
            $table->dropIndex('exploration_warnings_character_dismissed_index');
            $table->dropColumn('dismissed_at');
        });

        Schema::table('delve_explorations', function (Blueprint $table): void {
            $table->dropIndex('delve_explorations_character_dismissed_index');
            $table->dropColumn(['ended_reason', 'panel_dismissed_at']);
        });
    }
};
