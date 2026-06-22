<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raid_boss_participations', function (Blueprint $table): void {
            $table->foreignId('raid_boss_id')
                ->nullable()
                ->after('raid_id')
                ->constrained('raid_bosses');
            $table->index(
                ['character_id', 'raid_id', 'raid_boss_id'],
                'raid_boss_participations_character_raid_boss_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('raid_boss_participations', function (Blueprint $table): void {
            $table->dropIndex('raid_boss_participations_character_raid_boss_index');
            $table->dropConstrainedForeignId('raid_boss_id');
        });
    }
};
