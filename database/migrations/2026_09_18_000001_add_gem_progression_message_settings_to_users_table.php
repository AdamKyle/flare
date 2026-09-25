<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the Global/Personal Gem Progression XP server message preferences to Users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('show_global_gem_progression_xp_messages')->default(true)->after('show_copper_coins_per_kill');
            $table->boolean('show_personal_gem_progression_xp_messages')->default(true)->after('show_global_gem_progression_xp_messages');
        });
    }

    /**
     * Remove the Global/Personal Gem Progression XP server message preferences from Users.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'show_global_gem_progression_xp_messages',
                'show_personal_gem_progression_xp_messages',
            ]);
        });
    }
};
