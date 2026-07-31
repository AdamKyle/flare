<?php

namespace Tests\Feature\Admin\Gems;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class GeneratedGemProfileDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_map_gem_file_has_required_maps_only(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/World Gems/map-gems.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $this->assertSame(6, $records->count());
        $this->assertTrue($records->pluck('game_map_name')->contains('Shadow Plane'));
        $this->assertTrue($records->pluck('game_map_name')->contains('Hell'));
        $this->assertTrue($records->pluck('game_map_name')->contains('Purgatory'));
        $this->assertTrue($records->pluck('game_map_name')->contains('Twisted Memories'));
        $this->assertTrue($records->pluck('game_map_name')->contains('Delusional Memories'));
        $this->assertTrue($records->pluck('game_map_name')->contains('The Ice Plane'));
        $this->assertFalse($records->pluck('game_map_name')->contains('Surface'));
        $this->assertFalse($records->pluck('game_map_name')->contains('Dungeons'));
        $this->assertFalse($records->pluck('game_map_name')->contains('Labyrinth'));
    }

    public function test_generated_map_gem_file_values_follow_configured_ranges(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/World Gems/map-gems.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $shadowPlane = $records->firstWhere('game_map_name', 'Shadow Plane');
        $hell = $records->firstWhere('game_map_name', 'Hell');
        $purgatory = $records->firstWhere('game_map_name', 'Purgatory');
        $twistedMemories = $records->firstWhere('game_map_name', 'Twisted Memories');
        $delusionalMemories = $records->firstWhere('game_map_name', 'Delusional Memories');
        $icePlane = $records->firstWhere('game_map_name', 'The Ice Plane');

        $this->assertSame('0.08-0.15', $shadowPlane['character_xp_bonus_range']);
        $this->assertSame('0.06-0.12', $shadowPlane['enemy_strength_increase_range']);
        $this->assertSame('0.04-0.12', $shadowPlane['character_power_reduction_range']);
        $this->assertSame('0.12-0.2', $hell['character_xp_bonus_range']);
        $this->assertSame('0.18-0.25', $hell['enemy_strength_increase_range']);
        $this->assertSame('0.25-0.38', $purgatory['character_xp_bonus_range']);
        $this->assertSame('0.3-0.55', $purgatory['enemy_strength_increase_range']);
        $this->assertSame('0.35-0.43', $twistedMemories['character_xp_bonus_range']);
        $this->assertSame('0.48-0.65', $twistedMemories['enemy_strength_increase_range']);
        $this->assertSame($twistedMemories['character_xp_bonus_range'], $delusionalMemories['character_xp_bonus_range']);
        $this->assertSame($twistedMemories['enemy_strength_increase_range'], $delusionalMemories['enemy_strength_increase_range']);
        $this->assertSame($purgatory['character_xp_bonus_range'], $icePlane['character_xp_bonus_range']);
        $this->assertSame($purgatory['enemy_strength_increase_range'], $icePlane['enemy_strength_increase_range']);
    }

    public function test_generated_location_gem_file_contains_every_required_location(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/World Gems/location-gems.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $this->assertSame(33, $records->count());
        $this->assertSame(33, $records->pluck('location_name')->unique()->count());
        $this->assertTrue($records->pluck('location_name')->contains('Shadow Caves'));
        $this->assertTrue($records->pluck('location_name')->contains('Labyrinth Maze'));
        $this->assertTrue($records->pluck('location_name')->contains('Dungeons of Valifore'));
        $this->assertTrue($records->pluck('location_name')->contains('Wrecked Ship'));
        $this->assertTrue($records->pluck('location_name')->contains('Satans Cage'));
        $this->assertTrue($records->pluck('location_name')->contains('Bandits Twisted Arm Port'));
        $this->assertTrue($records->pluck('location_name')->contains('Church of God'));
        $this->assertTrue($records->pluck('location_name')->contains('Emerald Mining Town'));
        $this->assertTrue($records->pluck('location_name')->contains('Twisted Memorial Crypt'));
        $this->assertTrue($records->pluck('location_name')->contains('Twisted grave site'));
        $this->assertTrue($records->pluck('location_name')->contains('Abandoned Village'));
        $this->assertTrue($records->pluck('location_name')->contains('Banshee Fields of Tomorrow'));
        $this->assertTrue($records->pluck('location_name')->contains('Bloody Snowman'));
        $this->assertTrue($records->pluck('location_name')->contains('Broken Forest Road'));
        $this->assertTrue($records->pluck('location_name')->contains('Frozen Pet Cemetary'));
        $this->assertTrue($records->pluck('location_name')->contains('Frozen Queens Bank'));
        $this->assertTrue($records->pluck('location_name')->contains('The Frozen Wreck'));
        $this->assertTrue($records->pluck('location_name')->contains('Abandonded Chapel'));
        $this->assertTrue($records->pluck('location_name')->contains('Delusional Abandoned Gold Mines'));
        $this->assertTrue($records->pluck('location_name')->contains('Federation Controlled Town'));
        $this->assertTrue($records->pluck('location_name')->contains('Underwater Caves'));
        $this->assertTrue($records->pluck('location_name')->contains('Gold Mine'));
        $this->assertTrue($records->pluck('location_name')->contains('Lords Stronghold'));
        $this->assertTrue($records->pluck('location_name')->contains('Hells Broken Anvil'));
        $this->assertTrue($records->pluck('location_name')->contains('Tear in the Fabric of Time'));
        $this->assertTrue($records->pluck('location_name')->contains('Twisted Dimensional Gate'));
        $this->assertTrue($records->pluck('location_name')->contains('Cave of Memories'));
        $this->assertTrue($records->pluck('location_name')->contains('Purgatories Dungeons'));
        $this->assertTrue($records->pluck('location_name')->contains('Purgatory Smiths House'));
        $this->assertTrue($records->pluck('location_name')->contains('Cave of Shadows'));
        $this->assertTrue($records->pluck('location_name')->contains('Dungeons of the twisted maiden'));
        $this->assertTrue($records->pluck('location_name')->contains('The Old Church'));
        $this->assertTrue($records->pluck('location_name')->contains('Alchemy Corrupted Church'));
    }

    public function test_generated_location_gem_file_uses_five_percent_higher_plane_ranges(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/World Gems/location-gems.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $shadowCaves = $records->firstWhere('location_name', 'Shadow Caves');
        $wreckedShip = $records->firstWhere('location_name', 'Wrecked Ship');
        $satansCage = $records->firstWhere('location_name', 'Satans Cage');
        $caveOfMemories = $records->firstWhere('location_name', 'Cave of Memories');
        $churchOfGod = $records->firstWhere('location_name', 'Church of God');
        $abandonedVillage = $records->firstWhere('location_name', 'Abandoned Village');

        $this->assertSame('0.084-0.1575', $shadowCaves['character_xp_bonus_range']);
        $this->assertSame('0.063-0.126', $shadowCaves['enemy_strength_increase_range']);
        $this->assertSame('0.084-0.1575', $wreckedShip['character_xp_bonus_range']);
        $this->assertSame('0.063-0.126', $wreckedShip['enemy_strength_increase_range']);
        $this->assertSame('0.126-0.21', $satansCage['character_xp_bonus_range']);
        $this->assertSame('0.189-0.2625', $satansCage['enemy_strength_increase_range']);
        $this->assertSame('0.2625-0.399', $caveOfMemories['character_xp_bonus_range']);
        $this->assertSame('0.315-0.5775', $caveOfMemories['enemy_strength_increase_range']);
        $this->assertSame('0.3675-0.4515', $churchOfGod['character_xp_bonus_range']);
        $this->assertSame('0.504-0.6825', $churchOfGod['enemy_strength_increase_range']);
        $this->assertSame('0.2625-0.399', $abandonedVillage['character_xp_bonus_range']);
        $this->assertSame('0.315-0.5775', $abandonedVillage['enemy_strength_increase_range']);
    }
}
