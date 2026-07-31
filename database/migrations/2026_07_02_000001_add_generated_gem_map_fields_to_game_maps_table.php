<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->string('generated_map_type')->nullable()->after('can_traverse');
            $table->unsignedBigInteger('generated_parent_game_map_id')->nullable()->after('generated_map_type');
            $table->unsignedBigInteger('game_map_gem_paramter_id')->nullable()->unique()->after('generated_parent_game_map_id');
            $table->unsignedBigInteger('game_location_gem_paramter_id')->nullable()->unique()->after('game_map_gem_paramter_id');

            $table->foreign('generated_parent_game_map_id', 'game_maps_generated_parent_fk')
                ->references('id')
                ->on('game_maps')
                ->nullOnDelete();

            $table->foreign('game_map_gem_paramter_id', 'game_maps_map_gem_paramter_fk')
                ->references('id')
                ->on('game_map_gem_paramters')
                ->cascadeOnDelete();

            $table->foreign('game_location_gem_paramter_id', 'game_maps_location_gem_paramter_fk')
                ->references('id')
                ->on('game_location_gem_paramters')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->dropForeign('game_maps_location_gem_paramter_fk');
            $table->dropForeign('game_maps_map_gem_paramter_fk');
            $table->dropForeign('game_maps_generated_parent_fk');
            $table->dropColumn([
                'game_location_gem_paramter_id',
                'game_map_gem_paramter_id',
                'generated_parent_game_map_id',
                'generated_map_type',
            ]);
        });
    }
};
