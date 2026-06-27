<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GameSkill;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Automation\Events\ExplorationOutputUpdated;
use App\Game\Automation\Events\ExplorationOutputUpdated;
use App\Game\Automation\Events\ExplorationWarningState;
use App\Game\Automation\Events\ExplorationWarningState;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\BattleRewardProcessing\Jobs\Events\WinterEventChristmasGiftHandler;
use App\Game\BattleRewardProcessing\Services\BattleLocationRewardService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\DropCheckService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateScheduledEvent;

class BattleRewardServiceTest extends TestCase
{
    use CreateCharacterAutomation, CreateEvent, CreateExplorationLog, CreateGameMap, CreateGlobalEventGoal, CreateItem, CreateItemAffix, CreateMonster, CreateScheduledEvent, RefreshDatabase;

    private ?BattleRewardService $battleRewardService;

    private ?CharacterFactory $characterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createItemAffix(['type' => 'suffix']);
        $this->createItemAffix(['type' => 'prefix']);
        $this->createItem([
            'type' => 'weapon',
            'specialty_type' => null,
            'skill_level_required' => 1,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $this->battleRewardService = resolve(BattleRewardService::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        if (ModelsEvent::count() > 0) {
            foreach (ModelsEvent::all() as $event) {
                $event->delete();
            }
        }

        parent::tearDown();

        $this->battleRewardService = null;

        $this->characterFactory = null;
    }

    public function test_should_not_update_character_currencies_when_not_logged_in(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        Event::assertNotDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function test_character_xp_is_awarded_after_currency_rewards(): void
    {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('distributeSkillXP');
        $characterRewardService->shouldReceive('giveCurrencies')
            ->once()
            ->globally()
            ->ordered()
            ->andReturn([]);
        $characterRewardService->shouldReceive('distributeCharacterXP')
            ->once()
            ->globally()
            ->ordered();

        $this->instance(CharacterRewardService::class, $characterRewardService);

        Event::fake();
        Queue::fake();

        resolve(BattleRewardService::class)->setUp($character->id, $monster->id)->processRewards();
    }

    public function test_character_xp_is_awarded_after_item_drops(): void
    {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);
        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('process')
            ->once()
            ->globally()
            ->ordered()
            ->andReturn([]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('distributeSkillXP');
        $characterRewardService->shouldReceive('giveCurrencies')->andReturn([]);
        $characterRewardService->shouldReceive('distributeCharacterXP')
            ->once()
            ->globally()
            ->ordered();

        $this->instance(DropCheckService::class, $dropCheckService);
        $this->instance(CharacterRewardService::class, $characterRewardService);

        Event::fake();
        Queue::fake();

        resolve(BattleRewardService::class)->setUp($character->id, $monster->id)->processRewards();
    }

    public function test_character_xp_is_awarded_after_faction_loyalty_bounty_handling(): void
    {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);
        $factionLoyaltyBountyHandler = Mockery::mock(FactionLoyaltyBountyHandler::class);
        $factionLoyaltyBountyHandler->shouldReceive('handleBounty')
            ->once()
            ->globally()
            ->ordered();
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('distributeSkillXP');
        $characterRewardService->shouldReceive('giveCurrencies')->andReturn([]);
        $characterRewardService->shouldReceive('distributeCharacterXP')
            ->once()
            ->globally()
            ->ordered();

        $this->instance(FactionLoyaltyBountyHandler::class, $factionLoyaltyBountyHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);

        Event::fake();
        Queue::fake();

        resolve(BattleRewardService::class)->setUp($character->id, $monster->id)->processRewards();
    }

    public function test_should_receive_less_xp_when_training_a_skill(): void
    {
        $character = $this->characterFactory->getCharacter();
        $initialXp = $character->xp;

        $accuracySkill = GameSkill::where('name', 'Accuracy')->first();

        $character->skills()->where('game_skill_id', $accuracySkill->id)->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 50,
        ]);

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id' => '1',
            'user_id' => $character->refresh()->user->id,
            'ip_address' => '1',
            'user_agent' => '1',
            'payload' => '1',
            'last_activity' => 1602801731,
        ]]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertLessThan($monster->xp, $character->xp - $initialXp);
    }

    public function test_should_receive_full_xp_when_training_a_skill_that_is_max_level(): void
    {
        $character = $this->characterFactory->getCharacter();
        $initialXp = $character->xp;

        $accuracySkill = GameSkill::where('name', 'Accuracy')->first();

        $character->skills()->where('game_skill_id', $accuracySkill->id)->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
            'level' => $accuracySkill->max_level,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 50,
            'max_level' => 9999,
        ]);

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id' => '1',
            'user_id' => $character->refresh()->user->id,
            'ip_address' => '1',
            'user_agent' => '1',
            'payload' => '1',
            'last_activity' => 1602801731,
        ]]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertEquals($monster->xp, $character->xp - $initialXp);
    }

    public function test_should_update_character_currencies_when_logged_in(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id' => '1',
            'user_id' => $character->refresh()->user->id,
            'ip_address' => '1',
            'user_agent' => '1',
            'payload' => '1',
            'last_activity' => 1602801731,
        ]]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function test_battle_rewards_pass_actual_gold_gained_into_gold_rush(): void
    {
        GoldRushCheckCalculator::shouldReceive('fetchGoldRushChance')->once()->andReturnTrue();

        $character = $this->characterFactory->getCharacter();
        $character->update([
            'gold' => 100000,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'gold' => 1000,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $this->assertEquals(101050, $character->refresh()->gold);
    }

    public function test_should_get_faction_points(): void
    {
        $character = $this->characterFactory->assignFactionSystem()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();

        $this->assertNotNull($faction);

        $this->assertGreaterThan(0, $faction->current_points);
    }

    public function test_process_rewards_does_not_award_faction_points_when_batch_context_passes_zero(): void
    {
        $character = $this->characterFactory->assignFactionSystem()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 1,
                'total_xp' => $monster->xp,
                'total_skill_xp' => 0,
                'total_faction_points' => 0,
            ])
            ->processRewards();

        $faction = $character->refresh()->factions()->where('game_map_id', $character->map->game_map_id)->first();

        $this->assertNotNull($faction);
        $this->assertEquals(0, $faction->current_points);
    }

    public function test_should_not_update_global_event_participation_when_no_event_is_running(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function test_should_not_update_global_event_participation_when_no_global_event_is_running(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function test_should_update_global_event_participation(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventParticipation);
    }

    public function test_should_update_global_event_participation_when_participation_exists(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 1,
            'current_crafts' => null,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'kills' => 1,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertEquals(2, $character->globalEventParticipation->current_kills);
        $this->assertEquals(2, $character->globalEventKills->kills);
    }

    public function test_no_faction_rewards_given_when_character_is_in_purgatory(): void
    {
        $character = $this->characterFactory->assignFactionSystem()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::PURGATORY,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        foreach ($character->factions as $faction) {
            $this->assertEquals(0, $faction->current_points);
        }
    }

    public function test_winter_event_christmas_gift_handler_is_dispatched_when_included(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards(true);

        Queue::assertPushed(WinterEventChristmasGiftHandler::class);
    }

    public function test_process_rewards_returns_early_when_character_cannot_be_found(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp(999999999, $monster->id)->processRewards(true);

        Queue::assertNotPushed(WinterEventChristmasGiftHandler::class);
        Event::assertNotDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function test_process_rewards_returns_early_when_monster_cannot_be_found(): void
    {
        $character = $this->characterFactory->getCharacter();

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, 999999999)->processRewards(true);

        Queue::assertNotPushed(WinterEventChristmasGiftHandler::class);
        Event::assertNotDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function test_process_rewards_uses_context_to_process_batch_rewards(): void
    {
        $character = $this->characterFactory->assignFactionSystem()->getCharacter();

        $accuracySkill = GameSkill::where('name', 'Accuracy')->first();

        $character->skills()->where('game_skill_id', $accuracySkill->id)->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $character = $character->refresh();

        $initialXp = $character->xp;
        $initialTrainingSkillXp = $character->skills()->where('game_skill_id', $accuracySkill->id)->first()->xp;

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 1,
        ]);

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 2,
                'total_xp' => 10,
                'total_skill_xp' => 10,
                'total_faction_points' => 5,
            ])
            ->processRewards();

        $character = $character->refresh();

        $this->assertEquals($initialXp + 10, $character->xp);

        $trainingSkill = $character->skills()->where('game_skill_id', $accuracySkill->id)->first();
        $this->assertEquals($initialTrainingSkillXp + 10, $trainingSkill->xp);

        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();
        $this->assertEquals(5, $faction->current_points);
    }

    public function test_process_rewards_can_skip_faction_loyalty_update_event_for_batch_automation_rewards(): void
    {
        $character = $this->characterFactory->getCharacter();
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($character);

        $character = $factionLoyaltyFactory->getCharacter();
        $factionLoyaltyNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $monster = $factionLoyaltyFactory->getBountyMonstersForNpc($factionLoyaltyNpc)[0];

        Event::fake();
        Queue::fake();

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 1,
                'total_xp' => 10,
                'total_skill_xp' => 0,
                'total_faction_points' => 0,
                'skip_faction_loyalty_update_event' => true,
            ])
            ->processRewards();

        $matchingTask = collect($factionLoyaltyNpc->refresh()->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['monster_id'] ?? null) === $monster->id);

        $this->assertEquals(1, $matchingTask['current_amount']);
        Event::assertNotDispatched(FactionLoyaltyUpdate::class);
    }

    public function test_no_faction_rewards_given_when_character_is_auto_battling(): void
    {
        $character = $this->characterFactory
            ->assignFactionSystem()
            ->getCharacter();

        $character->currentAutomations()->create([
            'character_id' => $character->id,
            'monster_id' => $this->createMonster()->id,
            'type' => 0,
            'started_at' => now(),
            'completed_at' => now()->addDay(),
            'move_down_monster_list_every' => null,
            'previous_level' => 10,
            'current_level' => 20,
            'attack_type' => 'attack',
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 1,
        ]);

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();
        $this->assertEquals(0, $faction->current_points);
    }

    public function test_process_rewards_awards_batch_faction_points_when_character_is_auto_battling(): void
    {
        $character = $this->characterFactory
            ->assignFactionSystem()
            ->getCharacter();

        $character->currentAutomations()->create([
            'character_id' => $character->id,
            'monster_id' => $this->createMonster()->id,
            'type' => 0,
            'started_at' => now(),
            'completed_at' => now()->addDay(),
            'move_down_monster_list_every' => null,
            'previous_level' => 10,
            'current_level' => 20,
            'attack_type' => 'attack',
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 1,
        ]);

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 1,
                'total_xp' => 10,
                'total_skill_xp' => 0,
                'total_faction_points' => 5,
            ])
            ->processRewards();

        $character = $character->refresh();

        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();
        $this->assertEquals(5, $faction->current_points);
    }

    public function test_process_rewards_with_exploration_log_id_updates_log_totals_and_broadcasts(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'currently_running' => true,
        ]);

        $copperCoinsItem = $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $this->characterFactory->assignFactionSystem();

        $character = $this->characterFactory->inventoryManagement()->giveItem($copperCoinsItem)->getCharacter();

        $accuracySkill = GameSkill::where('name', 'Accuracy')->first();

        $character->skills()->where('game_skill_id', $accuracySkill->id)->update([
            'currently_training' => true,
            'xp' => 0,
        ]);

        $character->update([
            'level' => 1,
            'xp' => 0,
            'xp_next' => 100,
            'gold' => MaxCurrenciesValue::MAX_GOLD - 1,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST - 1,
            'shards' => MaxCurrenciesValue::MAX_SHARDS - 1,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER - 1,
        ]);

        $character = $character->refresh();
        $trainingSkill = $character->skills()->where('game_skill_id', $accuracySkill->id)->first();
        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();

        $beforeSnapshot = [
            'xp' => $character->xp,
            'skill_xp' => $trainingSkill->xp,
            'faction_points' => $faction->current_points,
            'level' => $character->level,
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
            'copper_coins' => $character->copper_coins,
        ];

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addSeconds(3),
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 1,
            'gold' => 100,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 2,
                'total_xp' => 125,
                'total_skill_xp' => 50,
                'total_faction_points' => 10,
                'exploration_log_id' => $log->id,
            ])
            ->processRewards();

        $character = $character->refresh();
        $trainingSkill = $character->skills()->where('game_skill_id', $accuracySkill->id)->first();
        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();
        $log->refresh();

        $expectedXpGained = max(0, $character->xp - $beforeSnapshot['xp']);
        $expectedSkillXpGained = max(0, $trainingSkill->xp - $beforeSnapshot['skill_xp']);
        $expectedFactionPointsGained = max(0, $faction->current_points - $beforeSnapshot['faction_points']);
        $expectedLevelsGained = max(0, $character->level - $beforeSnapshot['level']);
        $balanceGoldDelta = max(0, $character->gold - $beforeSnapshot['gold']);
        $balanceGoldDustDelta = max(0, $character->gold_dust - $beforeSnapshot['gold_dust']);
        $balanceShardsDelta = max(0, $character->shards - $beforeSnapshot['shards']);
        $balanceCopperCoinsDelta = max(0, $character->copper_coins - $beforeSnapshot['copper_coins']);

        $this->assertGreaterThan(0, $expectedXpGained);
        $this->assertGreaterThan(0, $expectedSkillXpGained);
        $this->assertGreaterThan(0, $expectedFactionPointsGained);
        $this->assertGreaterThan(0, $expectedLevelsGained);
        $this->assertEquals(1, $balanceGoldDelta);
        $this->assertEquals(1, $balanceGoldDustDelta);
        $this->assertEquals(1, $balanceShardsDelta);
        $this->assertEquals(1, $balanceCopperCoinsDelta);

        $this->assertEquals(125, $log->xp_gained);
        $this->assertNotEquals($expectedXpGained, $log->xp_gained);
        $this->assertEquals(50, $log->skill_xp_gained);
        $this->assertEquals(10, $log->faction_points_gained);
        $this->assertEquals(200, $log->currencies_gained['gold']);
        $this->assertGreaterThan($balanceGoldDustDelta, $log->currencies_gained['gold_dust']);
        $this->assertGreaterThan($balanceShardsDelta, $log->currencies_gained['shards']);
        $this->assertGreaterThan($balanceCopperCoinsDelta, $log->currencies_gained['copper_coins']);
        $this->assertEquals($expectedLevelsGained, $log->currencies_gained['levels_gained']);

        Event::assertDispatched(ExplorationWarningState::class, function (ExplorationWarningState $event): bool {
            return $event->has_warning === false && $event->warnings === [];
        });

        Event::assertDispatched(ExplorationOutputUpdated::class, function (ExplorationOutputUpdated $event): bool {
            return $event->type === 'active' && $event->output['totals']['xp'] === 125;
        });
    }

    public function test_process_rewards_with_exploration_log_id_includes_auto_sold_drop_gold_when_gold_is_capped(): void
    {
        $this->characterFactory->assignFactionSystem();

        $character = $this->characterFactory->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 1,
            'gold' => 0,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('process')
            ->once()
            ->andReturn(['auto_sold_gold' => 950]);

        $this->instance(DropCheckService::class, $dropCheckService);
        $battleRewardService = resolve(BattleRewardService::class);

        Event::fake();
        Queue::fake();

        $battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 1,
                'total_xp' => 25,
                'total_skill_xp' => 0,
                'total_faction_points' => 0,
                'exploration_log_id' => $log->id,
            ])
            ->processRewards();

        $log->refresh();

        $this->assertEquals(950, $log->currencies_gained['gold']);
    }

    public function test_apply_reward_context_logs_context_rewards_when_stored_deltas_are_zero_and_currency_deltas_remain(): void
    {
        $character = $this->characterFactory->assignFactionSystem()->getCharacter();

        $accuracySkill = GameSkill::where('name', 'Accuracy')->first();

        $character->skills()->where('game_skill_id', $accuracySkill->id)->update([
            'currently_training' => true,
            'xp' => 0,
        ]);

        $character->update([
            'level' => 2,
            'xp' => 0,
            'gold' => 25,
            'gold_dust' => 5,
            'shards' => 7,
            'copper_coins' => 9,
        ]);

        $character = $character->refresh();
        $trainingSkill = $character->skills()->where('game_skill_id', $accuracySkill->id)->first();
        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        Event::fake();

        ExplorationLogService::applyRewardContext(
            $log,
            $character,
            [
                'xp' => $character->xp,
                'skill_id' => $trainingSkill->id,
                'skill_xp' => $trainingSkill->xp,
                'faction_id' => $faction->id,
                'faction_points' => $faction->current_points,
                'level' => 1,
                'gold' => 10,
                'gold_dust' => 2,
                'shards' => 3,
                'copper_coins' => 4,
            ],
            [
                'total_xp' => 300,
                'total_skill_xp' => 125,
                'total_faction_points' => 45,
            ],
        );

        $log->refresh();

        $this->assertEquals(300, $log->xp_gained);
        $this->assertEquals(125, $log->skill_xp_gained);
        $this->assertEquals(45, $log->faction_points_gained);
        $this->assertEquals(15, $log->currencies_gained['gold']);
        $this->assertEquals(3, $log->currencies_gained['gold_dust']);
        $this->assertEquals(4, $log->currencies_gained['shards']);
        $this->assertEquals(5, $log->currencies_gained['copper_coins']);
        $this->assertEquals(1, $log->currencies_gained['levels_gained']);
    }

    public function test_process_rewards_with_exploration_log_id_records_earned_currencies_when_all_balances_are_capped(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'currently_running' => true,
        ]);

        $copperCoinsItem = $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $this->characterFactory->assignFactionSystem();

        $character = $this->characterFactory->inventoryManagement()->giveItem($copperCoinsItem)->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'gold' => 100,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 2,
                'total_xp' => 50,
                'total_skill_xp' => 0,
                'total_faction_points' => 0,
                'exploration_log_id' => $log->id,
            ])
            ->processRewards();

        $log->refresh();

        $this->assertEquals(200, $log->currencies_gained['gold']);
        $this->assertGreaterThan(0, $log->currencies_gained['gold_dust'] ?? 0);
        $this->assertGreaterThan(0, $log->currencies_gained['shards'] ?? 0);
        $this->assertGreaterThan(0, $log->currencies_gained['copper_coins'] ?? 0);
    }

    public function test_process_rewards_with_exploration_log_id_captures_location_earned_currencies_when_balances_are_capped(): void
    {
        $this->characterFactory->assignFactionSystem();

        $character = $this->characterFactory->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $locationRewardService = Mockery::mock(BattleLocationRewardService::class);
        $locationRewardService->shouldReceive('setContext')->andReturnSelf();
        $locationRewardService->shouldReceive('handleLocationSpecificRewards')
            ->andReturn(['gold_dust' => 500, 'shards' => 300]);

        $this->instance(BattleLocationRewardService::class, $locationRewardService);

        $battleRewardService = resolve(BattleRewardService::class);

        Event::fake();
        Queue::fake();

        $battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 1,
                'total_xp' => 10,
                'total_skill_xp' => 0,
                'total_faction_points' => 0,
                'exploration_log_id' => $log->id,
            ])
            ->processRewards();

        $log->refresh();

        $this->assertEquals(500, $log->currencies_gained['gold_dust'] ?? 0);
        $this->assertEquals(300, $log->currencies_gained['shards'] ?? 0);
    }

    public function test_process_rewards_without_exploration_log_id_does_not_write_to_exploration_log(): void
    {
        $this->characterFactory->assignFactionSystem();

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 1,
                'total_xp' => 50,
                'total_skill_xp' => 0,
                'total_faction_points' => 0,
            ])
            ->processRewards();

        $log->refresh();

        $this->assertNull($log->currencies_gained);
    }

    public function test_should_update_global_event_participation_uses_context_kill_count(): void
    {

        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 1,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 1,
            'current_crafts' => null,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'kills' => 1,
        ]);

        $this->battleRewardService
            ->setUp($character->id, $monster->id)
            ->setContext([
                'total_creatures' => 2,
                'total_xp' => 1,
            ])
            ->processRewards();

        $character = $character->refresh();

        $this->assertEquals(3, $character->globalEventParticipation->current_kills);
        $this->assertEquals(3, $character->globalEventKills->kills);
    }

    public function test_should_not_update_global_event_participation_when_character_not_on_event_map(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function test_should_update_global_event_participation_for_delusional_memories_event(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::DELUSIONAL_MEMORIES,
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventParticipation);
    }

    public function test_manual_battle_kills_on_event_map_count_for_that_maps_event_goal(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventParticipation);
        $this->assertEquals(1, $character->globalEventParticipation->current_kills);
    }

    public function test_manual_battle_kills_do_not_count_when_max_kills_is_null(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => null,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function test_delusional_memories_kills_do_not_count_when_current_step_is_craft(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::DELUSIONAL_MEMORIES,
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function test_delusional_memories_kills_do_not_count_when_current_step_is_enchant(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::DELUSIONAL_MEMORIES,
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function test_event_kill_counting_resolves_event_goal_from_characters_current_map(): void
    {
        $character = $this->characterFactory->getCharacter();

        $winterMap = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        $character->map()->update([
            'game_map_id' => $winterMap->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $winterGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();
        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards();

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventParticipation);
        $this->assertEquals($winterGoal->id, $character->globalEventParticipation->global_event_goal_id);
    }
}
