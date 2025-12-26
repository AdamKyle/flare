<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GameSkill;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\BattleRewardProcessing\Jobs\Events\WinterEventChristmasGiftHandler;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMonster;

class BattleRewardServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, CreateMonster, RefreshDatabase, CreateItem, CreateItemAffix;

    private ?BattleRewardService $battleRewardService;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void
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

    public function tearDown(): void
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

    public function testShouldNotUpdateCharacterCurrenciesWhenNotLoggedIn(): void
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

    public function testShouldReceiveLessXpWhenTrainingASkill(): void
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

    public function testShouldReceiveFullXpWhenTrainingASkillThatIsMaxLevel(): void
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

    public function testShouldUpdateCharacterCurrenciesWhenLoggedIn(): void
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

    public function testShouldGetFactionPoints(): void
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

    public function testShouldNotUpdateGlobalEventParticipationWhenNoEventIsRunning(): void
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

    public function testShouldNotUpdateGlobalEventParticipationWhenNoGlobalEventIsRunning(): void
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

    public function testShouldUpdateGlobalEventParticipation(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
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

    public function testShouldUpdateGlobalEventParticipationWhenParticipationExists(): void
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
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

    public function testNoFactionRewardsGivenWhenCharacterIsInPurgatory(): void
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

    public function testWinterEventChristmasGiftHandlerIsDispatchedWhenIncluded(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Queue::fake();

        $this->battleRewardService->setUp($character->id, $monster->id)->processRewards(true);

        Queue::assertPushed(WinterEventChristmasGiftHandler::class);
    }
}
