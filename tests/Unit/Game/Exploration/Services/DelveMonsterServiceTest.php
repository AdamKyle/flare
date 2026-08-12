<?php

namespace Tests\Unit\Game\Exploration\Services;

use App\Flare\Models\Character;
use App\Game\Exploration\Services\DelveMonsterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class DelveMonsterServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?DelveMonsterService $service;

    private ?CharacterFactory $characterFactory;

    private ?Character $character;

    private ?array $monster;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(DelveMonsterService::class);

        $this->characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();

        $this->character = $this->characterFactory->getCharacter();

        $this->monster = [
            'damage_stat' => 'str',
            'str' => 10,
            'dex' => 10,
            'agi' => 10,
            'dur' => 10,
            'chr' => 10,
            'int' => 10,
            'ac' => 10,
            'spell_damage' => 10,
            'max_affix_damage' => 10,
            'spell_evasion' => 0.1,
            'affix_resistance' => 0.1,
            'max_healing' => 0.1,
            'entrancing_chance' => 0.1,
            'devouring_light_chance' => 0.1,
            'devouring_darkness_chance' => 0.1,
            'accuracy' => 0.1,
            'casting_accuracy' => 0.1,
            'dodge' => 0.1,
            'criticality' => 0.1,
            'ambush_chance' => 0.1,
            'counter_chance' => 0.1,
            'counter_resistance_chance' => 0.1,
            'ambush_resistance_chance' => 0.1,
            'increases_damage_by' => 0.1,
            'life_stealing_resistance' => 0.1,
            'health_range' => '10-20',
            'attack_range' => '5-15',
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
        $this->characterFactory = null;
        $this->character = null;
        $this->monster = null;
    }

    public function test_create_monster_returns_monster_unchanged_when_no_active_delve_exists(): void
    {
        $result = $this->service->createMonster($this->monster, $this->character);

        $this->assertSame($this->monster, $result);
    }

    public function test_create_monster_returns_monster_unchanged_when_increase_enemy_strength_is_zero(): void
    {
        $this->characterFactory->automationManagement()->assignDelveAutomation([
            'increase_enemy_strength' => 0,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->service->createMonster($this->monster, $this->character);

        $this->assertSame($this->monster, $result);
    }

    public function test_create_monster_increases_monster_stats_and_ranges_when_delve_is_active(): void
    {
        $this->characterFactory->automationManagement()->assignDelveAutomation([
            'increase_enemy_strength' => 2,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->service->createMonster($this->monster, $this->character);

        $this->assertSame(30, $result['str']);
        $this->assertSame('30-40', $result['health_range']);
        $this->assertSame('25-35', $result['attack_range']);
        $this->assertSame(['fire' => 0, 'ice' => 0, 'water' => 0], $result['elemental_atonement']);
    }

    public function test_create_monster_caps_percentage_stats_at_one_point_two_five(): void
    {
        $this->characterFactory->automationManagement()->assignDelveAutomation([
            'increase_enemy_strength' => 5,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->service->createMonster($this->monster, $this->character);

        $this->assertSame(1.25, $result['spell_evasion']);
        $this->assertSame(1.25, $result['affix_resistance']);
    }
}
