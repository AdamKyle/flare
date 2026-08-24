<?php

namespace Tests\Unit\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Services\GemWorldPlaneGenerationSettings;
use ChristianEssl\LandmapGeneration\Struct\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class GemWorldPlaneGenerationSettingsTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_land_color_matches_each_known_plane(): void
    {
        $settings = new GemWorldPlaneGenerationSettings();

        $this->assertEquals(new Color(99, 70, 8), $settings->landColor($this->createGameMap(['name' => 'Labyrinth'])));
        $this->assertEquals(new Color(94, 74, 73), $settings->landColor($this->createGameMap(['name' => 'Dungeons'])));
        $this->assertEquals(new Color(128, 127, 126), $settings->landColor($this->createGameMap(['name' => 'Shadow Plane'])));
        $this->assertEquals(new Color(59, 46, 23), $settings->landColor($this->createGameMap(['name' => 'Hell'])));
        $this->assertEquals(new Color(0, 0, 0), $settings->landColor($this->createGameMap(['name' => 'Purgatory'])));
        $this->assertEquals(new Color(39, 84, 166), $settings->landColor($this->createGameMap(['name' => 'The Ice Plane'])));
        $this->assertEquals(new Color(91, 110, 96), $settings->landColor($this->createGameMap(['name' => 'Twisted Memories'])));
        $this->assertEquals(new Color(138, 79, 12), $settings->landColor($this->createGameMap(['name' => 'Delusional Memories'])));
    }

    public function test_land_color_defaults_for_an_unknown_plane(): void
    {
        $settings = new GemWorldPlaneGenerationSettings();

        $this->assertEquals(new Color(97, 83, 61), $settings->landColor($this->createGameMap(['name' => 'Surface'])));
    }

    public function test_water_color_matches_each_known_plane(): void
    {
        $settings = new GemWorldPlaneGenerationSettings();

        $this->assertEquals(new Color(162, 219, 118), $settings->waterColor($this->createGameMap(['name' => 'Dungeons'])));
        $this->assertEquals(new Color(100, 227, 250), $settings->waterColor($this->createGameMap(['name' => 'Shadow Plane'])));
        $this->assertEquals(new Color(97, 0, 16), $settings->waterColor($this->createGameMap(['name' => 'Hell'])));
        $this->assertEquals(new Color(255, 255, 255), $settings->waterColor($this->createGameMap(['name' => 'Purgatory'])));
        $this->assertEquals(new Color(195, 225, 250), $settings->waterColor($this->createGameMap(['name' => 'The Ice Plane'])));
        $this->assertEquals(new Color(18, 57, 87), $settings->waterColor($this->createGameMap(['name' => 'Twisted Memories'])));
        $this->assertEquals(new Color(66, 129, 178), $settings->waterColor($this->createGameMap(['name' => 'Delusional Memories'])));
    }

    public function test_water_color_defaults_for_an_unknown_plane(): void
    {
        $settings = new GemWorldPlaneGenerationSettings();

        $this->assertEquals(new Color(44, 86, 100), $settings->waterColor($this->createGameMap(['name' => 'Surface'])));
    }

    public function test_water_level_matches_each_known_plane(): void
    {
        $settings = new GemWorldPlaneGenerationSettings();

        $this->assertSame(55, $settings->waterLevel($this->createGameMap(['name' => 'Hell'])));
        $this->assertSame(85, $settings->waterLevel($this->createGameMap(['name' => 'Purgatory'])));
        $this->assertSame(25, $settings->waterLevel($this->createGameMap(['name' => 'The Ice Plane'])));
        $this->assertSame(75, $settings->waterLevel($this->createGameMap(['name' => 'Twisted Memories'])));
        $this->assertSame(40, $settings->waterLevel($this->createGameMap(['name' => 'Delusional Memories'])));
    }

    public function test_water_level_defaults_for_an_unknown_plane(): void
    {
        $settings = new GemWorldPlaneGenerationSettings();

        $this->assertSame(45, $settings->waterLevel($this->createGameMap(['name' => 'Surface'])));
    }
}
