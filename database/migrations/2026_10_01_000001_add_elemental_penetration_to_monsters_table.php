<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Fire, Ice, and Water elemental penetration to Monsters.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('monsters', function (Blueprint $table): void {
            $table->decimal('fire_penetration', 8, 4)->nullable()->default(0)->after('water_atonement');
            $table->decimal('ice_penetration', 8, 4)->nullable()->default(0)->after('fire_penetration');
            $table->decimal('water_penetration', 8, 4)->nullable()->default(0)->after('ice_penetration');
        });
    }

    /**
     * Remove Fire, Ice, and Water elemental penetration from Monsters.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('monsters', function (Blueprint $table): void {
            $table->dropColumn(['fire_penetration', 'ice_penetration', 'water_penetration']);
        });
    }
};
