<?php

namespace Tests\Console;


use App\Flare\Models\MaxLevelConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncreaseMaxLevelTest extends TestCase
{
    use RefreshDatabase;

    public function testInitialIncreaseMaxLevel()
    {

        $this->assertEquals(0, $this->artisan('increase:max_level'));

        $maxLevel = MaxLevelConfiguration::first();

        $this->assertNotNull($maxLevel);
        $this->assertEquals(3500, $maxLevel->max_level);
    }

    public function testIncreaseMaxLevelPastInitial()
    {
        MaxLevelConfiguration::create([
            'max_level'      => 3500,
            'half_way'       => ceil(3500 / 2),
            'three_quarters' => ceil(3500 * .75),
            'last_leg'       => 3400
        ]);

        $this->assertEquals(0, $this->artisan('increase:max_level'));

        $maxLevel = MaxLevelConfiguration::first();

        $this->assertNotNull($maxLevel);
        $this->assertEquals(3600, $maxLevel->max_level);
    }
}
