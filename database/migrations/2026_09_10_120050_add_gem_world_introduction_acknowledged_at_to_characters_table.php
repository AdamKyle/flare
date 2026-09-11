<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the server-owned Gem World introduction acknowledgement timestamp to Characters.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->timestamp('gem_world_introduction_acknowledged_at')->nullable()->after('base_damage_stat_mod');
        });
    }

    /**
     * Remove the Gem World introduction acknowledgement timestamp from Characters.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->dropColumn('gem_world_introduction_acknowledged_at');
        });
    }
};
