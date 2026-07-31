<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_monster_fights', function (Blueprint $table): void {
            $table->timestamp('reward_processed_at')->nullable()->after('monster_was_killed');
        });

        $duplicateGroups = DB::table('weekly_monster_fights')
            ->select('character_id', 'monster_id')
            ->groupBy('character_id', 'monster_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $duplicateGroup) {
            $rows = DB::table('weekly_monster_fights')
                ->where('character_id', $duplicateGroup->character_id)
                ->where('monster_id', $duplicateGroup->monster_id)
                ->orderBy('id')
                ->get();
            $keeper = $rows->first();

            DB::table('weekly_monster_fights')->where('id', $keeper->id)->update([
                'character_deaths' => $rows->max('character_deaths'),
                'monster_was_killed' => $rows->contains(fn ($row): bool => (bool) $row->monster_was_killed),
                'created_at' => $rows->whereNotNull('created_at')->min('created_at'),
                'updated_at' => $rows->whereNotNull('updated_at')->max('updated_at'),
            ]);

            DB::table('weekly_monster_fights')
                ->where('character_id', $duplicateGroup->character_id)
                ->where('monster_id', $duplicateGroup->monster_id)
                ->where('id', '!=', $keeper->id)
                ->delete();
        }

        Schema::table('weekly_monster_fights', function (Blueprint $table): void {
            $table->unique(['character_id', 'monster_id'], 'weekly_monster_fights_character_monster_unique');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_monster_fights', function (Blueprint $table): void {
            $table->dropUnique('weekly_monster_fights_character_monster_unique');
            $table->dropColumn('reward_processed_at');
        });
    }
};
