<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Monster;
use App\Flare\Values\AttackTypeValue;
use App\Game\Automation\Events\ExplorationOutputUpdated;
use App\Game\Automation\Services\ExplorationLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;

class ExplorationLogServiceTest extends TestCase
{
    use CreateCharacterAutomation;
    use CreateExplorationLog;
    use CreateExplorationWarning;
    use RefreshDatabase;

    private Character $character;

    private Monster $monster;

    private CharacterAutomation $automation;

    private ExplorationLogService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->getMonster();

        $this->automation = $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->service = new ExplorationLogService();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testStartCreatesExplorationLog(): void
    {
        $log = $this->service->start($this->character, $this->automation);

        $this->assertInstanceOf(ExplorationLog::class, $log);
        $this->assertEquals($this->character->id, $log->character_id);
        $this->assertEquals($this->character->user_id, $log->user_id);
        $this->assertEquals($this->automation->id, $log->character_automation_id);
        $this->assertEquals($this->automation->monster_id, $log->monster_id);
        $this->assertEquals(AttackTypeValue::ATTACK, $log->attack_type);
        $this->assertEquals($this->character->level, $log->starting_level);
        $this->assertNotNull($log->started_at);
        $this->assertNull($log->ended_at);
        $this->assertEquals(1, ExplorationLog::count());
    }

    public function testRecordFightTotalsAggregatesMultipleCalls(): void
    {
        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 5,
            'kills' => 3,
            'weapon_damage' => 1000,
            'spell_damage' => 500,
            'xp_gained' => 200,
            'skill_xp_gained' => 50,
            'faction_points_gained' => 10,
            'currencies_gained' => ['gold' => 100],
        ]);

        $this->service->recordFightTotals($log, [
            'fights' => 3,
            'kills' => 2,
            'weapon_damage' => 750,
            'spell_damage' => 250,
            'xp_gained' => 100,
            'skill_xp_gained' => 25,
            'faction_points_gained' => 5,
            'currencies_gained' => ['gold' => 50, 'gold_dust' => 20],
        ]);

        $log->refresh();

        $this->assertEquals(8, $log->fights);
        $this->assertEquals(5, $log->kills);
        $this->assertEquals(1750, $log->weapon_damage);
        $this->assertEquals(750, $log->spell_damage);
        $this->assertEquals(300, $log->xp_gained);
        $this->assertEquals(75, $log->skill_xp_gained);
        $this->assertEquals(15, $log->faction_points_gained);
        $this->assertEquals(['gold' => 150, 'gold_dust' => 20], $log->currencies_gained);
    }

    public function testFinalizeStoresEndedAtReasonAndSummary(): void
    {
        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 10,
            'kills' => 8,
            'xp_gained' => 500,
        ]);

        $this->service->finalize($log, 'player_stopped', true);

        $log->refresh();

        $this->assertNotNull($log->ended_at);
        $this->assertEquals('player_stopped', $log->stopped_reason);
        $this->assertTrue($log->stopped_by_player);
        $this->assertNotNull($log->summary);
        $this->assertEquals(10, $log->summary['fights']);
        $this->assertEquals(8, $log->summary['kills']);
        $this->assertEquals(500, $log->summary['xp_gained']);
    }

    public function testLatestForCharacterReturnsNewestLog(): void
    {
        $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $this->automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now()->subHour(),
        ]);

        $newer = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $this->automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $result = $this->service->latestForCharacter($this->character);

        $this->assertNotNull($result);
        $this->assertEquals($newer->id, $result->id);
    }

    public function testOutputReturnsActiveExplorationShapeAndBroadcastsAfterStart(): void
    {
        Event::fake();
        Carbon::setTestNow(now());

        $log = $this->service->start($this->character, $this->automation);
        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals($log->id, $output['output']['id']);
        $this->assertEquals($this->monster->id, $output['output']['monster']['id']);
        $this->assertEquals($this->monster->name, $output['output']['monster']['name']);
        $this->assertEquals('/monsters/'.$this->monster->id, $output['output']['monster']['link']);
        $this->assertEquals($this->monster->str, $output['output']['monster']['stats']['str']);
        $this->assertEquals(0, $output['output']['totals']['fights']);
        $this->assertEquals(0, $output['output']['damage']['weapon']);
        $this->assertEquals(0, $output['output']['healing']);
        $this->assertEquals(0, $output['output']['blocked']);
        $this->assertEquals(0, $output['output']['duration']);
        $this->assertEquals('running', $output['output']['reason']);
        $this->assertEquals('Exploration is running.', $output['output']['message']);

        Event::assertDispatched(ExplorationOutputUpdated::class, function (ExplorationOutputUpdated $event): bool {
            return $event->type === 'active'
                && $event->output['monster']['id'] === $this->monster->id;
        });
    }

    public function testOutputReturnsUpdatedExplorationShapeAndBroadcastsAfterTotals(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 4,
            'kills' => 3,
            'weapon_damage' => 1500,
            'spell_damage' => 600,
            'xp_gained' => 1000,
            'skill_xp_gained' => 250,
            'faction_points_gained' => 30,
            'healing_done' => 90,
            'damage_blocked' => 40,
            'currencies_gained' => [
                'gold' => 125,
                'shards' => 5,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Runtime '.$this->monster->name,
                'link' => '/monsters/runtime-'.$this->monster->id,
                'stats' => [
                    'str' => 11,
                    'dur' => 12,
                    'dex' => 13,
                    'chr' => 14,
                    'int' => 15,
                    'agi' => 16,
                    'focus' => 17,
                    'ac' => 18,
                    'health_range' => 19,
                    'attack_range' => 20,
                    'max_spell_damage' => 21,
                    'healing_percentage' => 22,
                    'xp' => 23,
                    'gold' => 24,
                    'max_level' => 25,
                ],
            ],
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals(4, $output['output']['totals']['fights']);
        $this->assertEquals(3, $output['output']['totals']['kills']);
        $this->assertEquals(1000, $output['output']['totals']['xp']);
        $this->assertEquals(250, $output['output']['totals']['skill_xp']);
        $this->assertEquals(30, $output['output']['totals']['faction_points']);
        $this->assertEquals(1500, $output['output']['damage']['weapon']);
        $this->assertEquals(600, $output['output']['damage']['spell']);
        $this->assertEquals(90, $output['output']['healing']);
        $this->assertEquals(40, $output['output']['blocked']);
        $this->assertEquals(125, $output['output']['currencies']['gold']);
        $this->assertEquals(5, $output['output']['currencies']['shards']);
        $this->assertEquals('Runtime '.$this->monster->name, $output['output']['monster']['name']);
        $this->assertEquals('/monsters/runtime-'.$this->monster->id, $output['output']['monster']['link']);
        $this->assertEquals(19, $output['output']['monster']['stats']['health_range']);
        $this->assertEquals(20, $output['output']['monster']['stats']['attack_range']);
        $this->assertEquals(21, $output['output']['monster']['stats']['max_spell_damage']);
        $this->assertEquals(22, $output['output']['monster']['stats']['healing_percentage']);
        $this->assertEquals(23, $output['output']['monster']['stats']['xp']);
        $this->assertEquals(24, $output['output']['monster']['stats']['gold']);
        $this->assertEquals(25, $output['output']['monster']['stats']['max_level']);

        Event::assertDispatched(ExplorationOutputUpdated::class, function (ExplorationOutputUpdated $event): bool {
            return $event->type === 'active'
                && $event->output['totals']['fights'] === 4
                && $event->output['damage']['weapon'] === 1500;
        });
    }

    public function testActiveOutputCalculatesLevelsGainedFromStartingLevel(): void
    {
        Event::fake();

        $startingLevel = $this->character->level;
        $this->service->start($this->character, $this->automation);
        $this->character->update(['level' => $startingLevel + 1000]);

        $output = $this->service->outputForCharacter($this->character->refresh());

        $this->assertEquals(1000, $output['output']['currencies']['levels_gained']);
        $this->assertEquals(1000, $output['output']['currencies_gained']['levels_gained']);
    }

    public function testActiveOutputIncludesLevelsGainedOutsideRewardProcessing(): void
    {
        Event::fake();

        $startingLevel = $this->character->level;
        $log = $this->service->start($this->character, $this->automation);
        $log->update(['currencies_gained' => ['levels_gained' => 200]]);
        $this->character->update(['level' => $startingLevel + 1000]);

        $output = $this->service->outputForCharacter($this->character->refresh());

        $this->assertEquals(1000, $output['output']['currencies']['levels_gained']);
    }

    public function testActiveOutputSupportsOldLogWithoutStartingLevel(): void
    {
        Event::fake();

        $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $this->automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'starting_level' => null,
            'started_at' => now(),
            'currencies_gained' => ['levels_gained' => 12],
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals(12, $output['output']['currencies']['levels_gained']);
    }

    public function testOutputExposesCurrentRoundCreaturesFromSummaryWithoutChangingCompletedFights(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 2,
            'kills' => 8,
        ]);

        $log->refresh();
        $summary = $log->summary ?? [];
        $summary['current_round_creatures'] = 11;
        $log->update([
            'summary' => $summary,
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals(11, $output['output']['current_round_creatures']);
        $this->assertEquals(2, $output['output']['totals']['fights']);
        $this->assertEquals(8, $output['output']['totals']['kills']);
    }

    public function testOutputPrefersRuntimeMonsterSnapshotCombatStatAliases(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 1,
            'kills' => 1,
            'weapon_damage' => 987,
            'spell_damage' => 654,
            'healing_done' => 321,
            'damage_blocked' => 123,
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Runtime '.$this->monster->name,
                'link' => '/monsters/runtime-'.$this->monster->id,
                'stats' => [
                    'str' => 31,
                    'dur' => 32,
                    'dex' => 33,
                    'chr' => 34,
                    'int' => 35,
                    'agi' => 36,
                    'focus' => 37,
                    'ac' => 38,
                    'health' => 3900,
                    'attack_damage' => 410,
                    'spell_damage' => 420,
                    'healing' => 43,
                    'xp' => 440,
                    'gold' => 450,
                    'max_level' => 46,
                ],
            ],
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals(987, $output['output']['damage']['weapon']);
        $this->assertEquals(654, $output['output']['damage']['spell']);
        $this->assertEquals(321, $output['output']['healing']);
        $this->assertEquals(123, $output['output']['blocked']);
        $this->assertEquals(3900, $output['output']['monster']['stats']['health_range']);
        $this->assertEquals(410, $output['output']['monster']['stats']['attack_range']);
        $this->assertEquals(420, $output['output']['monster']['stats']['max_spell_damage']);
        $this->assertEquals(43, $output['output']['monster']['stats']['healing_percentage']);
        $this->assertEquals(440, $output['output']['monster']['stats']['xp']);
        $this->assertEquals(450, $output['output']['monster']['stats']['gold']);
        $this->assertEquals(46, $output['output']['monster']['stats']['max_level']);
    }

    public function testOutputReturnsWarningShapeAndBroadcastsAfterFinalize(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 2,
            'kills' => 1,
            'xp_gained' => 500,
        ]);

        $this->service->finalize($log, 'inventory_full', false);

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'inventory_full',
            'message' => 'Your inventory is full.',
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('warning', $output['type']);
        $this->assertEquals($warning->id, $output['output']['id']);
        $this->assertEquals('inventory_full', $output['output']['reason']);
        $this->assertEquals('Your inventory is full.', $output['output']['message']);
        $this->assertEquals($this->monster->id, $output['output']['monster']['id']);
        $this->assertEquals(2, $output['output']['totals']['fights']);
        $this->assertEquals(1, $output['output']['totals']['kills']);
        $this->assertEquals(500, $output['output']['totals']['xp']);

        Event::assertDispatched(ExplorationOutputUpdated::class, function (ExplorationOutputUpdated $event): bool {
            return $event->type === 'warning'
                && $event->output['reason'] === 'inventory_full'
                && $event->output['message'] === 'Your inventory is full.';
        });
    }

    public function testEndedExplorationRemainsQueryableAfterCompletion(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'natural_end');

        $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'natural_end',
            'message' => 'Exploration completed.',
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('warning', $output['type']);
        $this->assertEquals($log->id, $output['output']['exploration_log_id']);
        $this->assertEquals(1, ExplorationLog::where('id', $log->id)->where('character_id', $this->character->id)->count());
    }

    public function testDismissHidesCompletedExplorationPanelWithoutDeletingLog(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'player_stopped', true);

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'player_stopped',
            'message' => 'Exploration has been stopped at player request.',
        ]);

        $this->service->clear($this->character, $warning);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertNull($output['type']);
        $this->assertNotNull($warning->refresh()->dismissed_at);
        $this->assertEquals(1, ExplorationLog::where('id', $log->id)->where('character_id', $this->character->id)->count());
    }

    public function testStartingNewExplorationOverridesDismissedCompletedPanel(): void
    {
        Event::fake();

        $completedLog = $this->service->start($this->character, $this->automation);
        $this->service->finalize($completedLog, 'player_stopped', true);

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $completedLog->id,
            'type' => 'player_stopped',
            'message' => 'Exploration has been stopped at player request.',
            'dismissed_at' => now(),
        ]);

        $newAutomation = $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $activeLog = $this->service->start($this->character, $newAutomation);
        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals($activeLog->id, $output['output']['id']);
        $this->assertNotNull($warning->refresh()->dismissed_at);
    }

    public function testBaseOutputShowsBaseMonsterHealthRangeAndAttackRangeWithNoSnapshot(): void
    {
        Event::fake();

        $this->monster->update([
            'health_range' => '50-200',
            'attack_range' => '10-40',
        ]);
        $this->monster = $this->monster->refresh();

        $log = $this->service->start($this->character, $this->automation);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals('50-200', $output['output']['monster']['stats']['health_range']);
        $this->assertEquals('10-40', $output['output']['monster']['stats']['attack_range']);
    }

    public function testOutputIncludesAttackDamageKeyInStatsWhenSnapshotHasIt(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 1,
            'kills' => 1,
            'monster' => [
                'id' => $this->monster->id,
                'name' => $this->monster->name,
                'link' => '/monsters/'.$this->monster->id,
                'stats' => [
                    'str' => 10,
                    'attack_damage' => 42,
                ],
            ],
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertArrayHasKey('attack_damage', $output['output']['monster']['stats']);
        $this->assertEquals(42, $output['output']['monster']['stats']['attack_damage']);
    }

    public function testOutputIncludesHealthKeyInStatsWhenSnapshotHasIt(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 1,
            'kills' => 1,
            'monster' => [
                'id' => $this->monster->id,
                'name' => $this->monster->name,
                'link' => '/monsters/'.$this->monster->id,
                'stats' => [
                    'str' => 10,
                    'health' => 500,
                ],
            ],
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertArrayHasKey('health', $output['output']['monster']['stats']);
        $this->assertEquals(500, $output['output']['monster']['stats']['health']);
    }

    public function testOutputExcludesAttackDamageAndHealthKeysWhenSnapshotLacksThem(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 1,
            'kills' => 1,
            'monster' => [
                'id' => $this->monster->id,
                'name' => $this->monster->name,
                'link' => '/monsters/'.$this->monster->id,
                'stats' => [
                    'str' => 10,
                    'attack_range' => '10-40',
                    'health_range' => '50-200',
                ],
            ],
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertArrayNotHasKey('attack_damage', $output['output']['monster']['stats']);
        $this->assertArrayNotHasKey('health', $output['output']['monster']['stats']);
    }

    public function testRecordFightTotalsBroadcastsByDefault(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, ['fights' => 1, 'kills' => 1]);

        Event::assertDispatched(ExplorationOutputUpdated::class);
    }

    public function testRecordFightTotalsDoesNotBroadcastWhenBroadcastIsFalse(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        Event::fake();

        $this->service->recordFightTotals($log, ['fights' => 1, 'kills' => 1], false);

        Event::assertNotDispatched(ExplorationOutputUpdated::class);
    }

    public function testRecordMonsterSnapshotBroadcastsByDefault(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordMonsterSnapshot($log, ['id' => $this->monster->id, 'name' => $this->monster->name]);

        Event::assertDispatched(ExplorationOutputUpdated::class);
    }

    public function testRecordMonsterSnapshotDoesNotBroadcastWhenBroadcastIsFalse(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        Event::fake();

        $this->service->recordMonsterSnapshot($log, ['id' => $this->monster->id, 'name' => $this->monster->name], false);

        Event::assertNotDispatched(ExplorationOutputUpdated::class);
    }

    public function testRecordCurrentRoundCreaturesBroadcastsByDefault(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordCurrentRoundCreatures($log, 5);

        Event::assertDispatched(ExplorationOutputUpdated::class);
    }

    public function testRecordCurrentRoundCreaturesDoesNotBroadcastWhenBroadcastIsFalse(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        Event::fake();

        $this->service->recordCurrentRoundCreatures($log, 5, false);

        Event::assertNotDispatched(ExplorationOutputUpdated::class);
    }

    public function testOutputReturnsNullShapeAndBroadcastsAfterClear(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'fight_failed');

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
        ]);

        $this->service->clear($this->character, $warning);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertNull($output['type']);
        $this->assertNull($output['output']);

        Event::assertDispatched(ExplorationOutputUpdated::class, function (ExplorationOutputUpdated $event): bool {
            return is_null($event->type)
                && is_null($event->output);
        });
    }

    public function testResolveOutputRepairsMissingAutomationAndReturnsWarningType(): void
    {
        Event::fake();
        Log::shouldReceive('error')->once();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => 999999,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('warning', $output['type']);
        $this->assertEquals('missing_automation', $output['output']['reason']);

        $log->refresh();

        $this->assertNotNull($log->ended_at);
        $this->assertEquals('missing_automation', $log->stopped_reason);
        $this->assertEquals(1, ExplorationWarning::where('character_id', $this->character->id)->count());
    }

    public function testResolveOutputDoesNotRepairWhenAutomationExists(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals($log->id, $output['output']['id']);

        $log->refresh();

        $this->assertNull($log->ended_at);
        $this->assertEquals(0, ExplorationWarning::where('character_id', $this->character->id)->count());
    }

    public function testStartSetsStoppedReasonToRunning(): void
    {
        $log = $this->service->start($this->character, $this->automation);

        $this->assertEquals('running', $log->stopped_reason);
    }

    public function testManuallyStoppedExplorationShowsAsEndedTypeWithNoWarning(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'player_stopped', true);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('ended', $output['type']);
        $this->assertNotNull($output['output']);
    }

    public function testEndedOutputContainsStoppedReasonAndReasonFields(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'player_stopped', true);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('ended', $output['type']);
        $this->assertEquals('player_stopped', $output['output']['stopped_reason']);
        $this->assertEquals('player_stopped', $output['output']['reason']);
        $this->assertEquals($log->id, $output['output']['id']);
        $this->assertEquals($this->monster->id, $output['output']['monster']['id']);
    }

    public function testDismissEndedLogSetsTimestampAndReturnedOutputIsNull(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'player_stopped', true);

        $this->service->dismissEndedLog($this->character);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertNull($output['type']);
        $this->assertNull($output['output']);
        $this->assertNotNull($log->refresh()->panel_dismissed_at);
    }

    public function testDismissEndedLogDoesNotDeleteTheLogRecord(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'player_stopped', true);

        $this->service->dismissEndedLog($this->character);

        $this->assertEquals(1, ExplorationLog::where('id', $log->id)->where('character_id', $this->character->id)->count());
    }

    public function testActiveLogTakesPriorityOverUndismissedEndedLog(): void
    {
        Event::fake();

        $endedLog = $this->service->start($this->character, $this->automation);
        $this->service->finalize($endedLog, 'player_stopped', true);

        $newAutomation = $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $activeLog = $this->service->start($this->character, $newAutomation);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals($activeLog->id, $output['output']['id']);
    }

    public function testWarningTakesPriorityOverUndismissedEndedLog(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'inventory_full', false);

        $warning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'inventory_full',
            'message' => 'Inventory full.',
        ]);

        $endedLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $this->automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now()->subHour(),
            'ended_at' => now()->subMinutes(30),
            'stopped_reason' => 'player_stopped',
        ]);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('warning', $output['type']);
        $this->assertEquals($warning->id, $output['output']['id']);
    }

    public function testFinalizeRetainsLogRecordAfterCompletion(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'natural_end', false);

        $this->assertEquals(1, ExplorationLog::where('id', $log->id)->where('character_id', $this->character->id)->count());
    }

    public function testCompletedLogRemainsQueryableAfterPanelDismissal(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);

        $this->service->recordFightTotals($log, [
            'fights' => 5,
            'kills' => 3,
            'xp_gained' => 250,
        ]);

        $this->service->finalize($log, 'player_stopped', true);
        $this->service->dismissEndedLog($this->character);

        $found = ExplorationLog::where('character_id', $this->character->id)
            ->whereNotNull('ended_at')
            ->whereNotNull('panel_dismissed_at')
            ->first();

        $this->assertNotNull($found);
        $this->assertEquals($log->id, $found->id);
        $this->assertEquals('player_stopped', $found->stopped_reason);
        $this->assertEquals(5, $found->fights);
        $this->assertEquals(3, $found->kills);
        $this->assertEquals(250, $found->xp_gained);
    }

    public function testDismissEndedLogBroadcastsOutput(): void
    {
        Event::fake();

        $log = $this->service->start($this->character, $this->automation);
        $this->service->finalize($log, 'player_stopped', true);

        Event::fake();

        $this->service->dismissEndedLog($this->character);

        Event::assertDispatched(ExplorationOutputUpdated::class, function (ExplorationOutputUpdated $event): bool {
            return is_null($event->type)
                && is_null($event->output);
        });
    }

    public function testStartingNewExplorationAfterDismissedEndedLogShowsActive(): void
    {
        Event::fake();

        $endedLog = $this->service->start($this->character, $this->automation);
        $this->service->finalize($endedLog, 'player_stopped', true);
        $this->service->dismissEndedLog($this->character);

        $newAutomation = $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $activeLog = $this->service->start($this->character, $newAutomation);

        $output = $this->service->outputForCharacter($this->character);

        $this->assertEquals('active', $output['type']);
        $this->assertEquals($activeLog->id, $output['output']['id']);
    }
}
