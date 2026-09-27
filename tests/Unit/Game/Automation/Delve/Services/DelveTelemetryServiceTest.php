<?php

namespace Tests\Unit\Game\Automation\Delve\Services;

use App\Game\Automation\Calculations\BattleMessageTotalsCalculator;
use App\Game\Automation\Delve\Enums\DelveOutcome;
use App\Game\Automation\Delve\Services\DelveTelemetryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateDelveLog;
use Tests\Traits\CreateMonster;

class DelveTelemetryServiceTest extends TestCase
{
    use CreateDelveExploration, CreateDelveLog, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?DelveTelemetryService $delveTelemetryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->delveTelemetryService = new DelveTelemetryService(new BattleMessageTotalsCalculator);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->delveTelemetryService = null;
    }

    public function test_telemetry_starts_with_a_zeroed_baseline_point_using_the_configured_pack_size(): void
    {
        $character = $this->character->getCharacter();

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $this->createMonster(['game_map_id' => $character->map->game_map_id])->id,
            'started_at' => Carbon::parse('2026-09-26 12:00:00'),
            'completed_at' => null,
            'pack_size' => 5,
        ])->refresh();

        $telemetry = $this->delveTelemetryService->telemetry($delve);

        $this->assertSame([
            [
                'elapsed_seconds' => 0,
                'rounds' => 0,
                'wins' => 0,
                'timeouts' => 0,
                'enemy_strength_increase' => 0,
                'pack_size' => 5,
                'weapon_damage' => 0,
                'spell_damage' => 0,
                'healing' => 0,
                'blocked' => 0,
            ],
        ], $telemetry['chart_points']);
        $this->assertSame(['rounds' => 0, 'wins' => 0, 'timeouts' => 0, 'pack_size' => 5, 'enemy_strength_increase' => 0], $telemetry['totals']);
    }

    public function test_first_successful_round_appends_a_cumulative_chart_point(): void
    {
        $character = $this->character->getCharacter();

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $this->createMonster(['game_map_id' => $character->map->game_map_id])->id,
            'started_at' => Carbon::parse('2026-09-26 12:00:00'),
            'completed_at' => null,
            'pack_size' => 5,
        ])->refresh();

        $this->createDelveLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 5,
            'outcome' => DelveOutcome::SURVIVED->value,
            'increased_enemy_strength' => 0,
            'fight_data' => [
                'attack_messages' => [
                    ['message' => 'Your weapon hits Goblin for: 1,200', 'type' => 'player-action'],
                    ['message' => 'Your damage spell(s) hits Goblin for: 300', 'type' => 'player-action'],
                    ['message' => 'You healed for: 45', 'type' => 'player-action'],
                    ['message' => 'You reduced the incoming (Physical) damage with your armour by: 20', 'type' => 'player-action'],
                ],
            ],
            'created_at' => Carbon::parse('2026-09-26 12:03:00'),
        ]);

        $telemetry = $this->delveTelemetryService->telemetry($delve);

        $this->assertSame([
            'elapsed_seconds' => 180,
            'rounds' => 1,
            'wins' => 1,
            'timeouts' => 0,
            'enemy_strength_increase' => 0.0,
            'pack_size' => 5,
            'weapon_damage' => 1200,
            'spell_damage' => 300,
            'healing' => 45,
            'blocked' => 20,
        ], $telemetry['chart_points'][1]);
        $this->assertSame(['weapon' => 1200, 'spell' => 300], $telemetry['damage']);
        $this->assertSame(45, $telemetry['healing']);
        $this->assertSame(20, $telemetry['blocked']);
    }

    public function test_subsequent_rounds_accumulate_instead_of_resetting(): void
    {
        $character = $this->character->getCharacter();

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $this->createMonster(['game_map_id' => $character->map->game_map_id])->id,
            'started_at' => Carbon::parse('2026-09-26 12:00:00'),
            'completed_at' => null,
            'pack_size' => 1,
        ])->refresh();

        $this->createDelveLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'outcome' => DelveOutcome::SURVIVED->value,
            'increased_enemy_strength' => 0,
            'fight_data' => ['attack_messages' => [['message' => 'Your weapon hits Goblin for: 1,200']]],
            'created_at' => Carbon::parse('2026-09-26 12:03:00'),
        ]);

        $this->createDelveLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'outcome' => DelveOutcome::SURVIVED->value,
            'increased_enemy_strength' => 0.05,
            'fight_data' => ['attack_messages' => [['message' => 'Your weapon hits Goblin for: 800']]],
            'created_at' => Carbon::parse('2026-09-26 12:06:00'),
        ]);

        $telemetry = $this->delveTelemetryService->telemetry($delve);

        $finalPoint = $telemetry['chart_points'][2];

        $this->assertCount(3, $telemetry['chart_points']);
        $this->assertSame(360, $finalPoint['elapsed_seconds']);
        $this->assertSame(2, $finalPoint['rounds']);
        $this->assertSame(2, $finalPoint['wins']);
        $this->assertSame(2000, $finalPoint['weapon_damage']);
        $this->assertSame(5.0, $finalPoint['enemy_strength_increase']);
        $this->assertSame(2, $telemetry['totals']['rounds']);
    }

    public function test_timeout_round_increments_the_timeout_count(): void
    {
        $character = $this->character->getCharacter();

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $this->createMonster(['game_map_id' => $character->map->game_map_id])->id,
            'started_at' => Carbon::parse('2026-09-26 12:00:00'),
            'completed_at' => null,
            'pack_size' => 1,
        ])->refresh();

        $this->createDelveLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'outcome' => DelveOutcome::TIMEOUT->value,
            'increased_enemy_strength' => 0,
            'fight_data' => [],
            'created_at' => Carbon::parse('2026-09-26 12:03:00'),
        ]);

        $telemetry = $this->delveTelemetryService->telemetry($delve);

        $this->assertSame(1, $telemetry['totals']['rounds']);
        $this->assertSame(0, $telemetry['totals']['wins']);
        $this->assertSame(1, $telemetry['totals']['timeouts']);
    }
}
