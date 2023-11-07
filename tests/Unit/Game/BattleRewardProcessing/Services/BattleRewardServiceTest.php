<?php

use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GameSkill;
use App\Flare\Values\ItemSpecialtyType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Setup\Character\CharacterFactory;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Battle\Jobs\BattleItemHandler;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;

class BattleRewardServiceTest extends TestCase {

    use RefreshDatabase, CreateMonster, CreateGameMap, CreateEvent, CreateGlobalEventGoal;

    private ?BattleRewardService $battleRewardService;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void {
        parent::setUp();

        $this->battleRewardService = resolve(BattleRewardService::class);

        $this->characterFactory    = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        if (ModelsEvent::count() > 0) {
            foreach (ModelsEvent::all() as $event) {
                $event->delete();
            }
        }

        parent::tearDown();

        $this->battleRewardService = null;

        $this->characterFactory = null;
    }

    public function testShouldNotUpdateCharacterCurrenciesWhenNotLoggedIn() {
        $character = $this->characterFactory->getCharacter();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        Event::assertNotDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function testShouldReceiveLessXpWhenTrainingASkill() {
        $character = $this->characterFactory->getCharacter();

        $character->skills()->where('game_skill_id', GameSkill::where('name', 'Accuracy')->first()->id)->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $character = $character->refresh();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp'          => 50,
        ]);

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->refresh()->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity' => 1602801731,
        ]]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);

        $character = $character->refresh();

        $this->assertLessThan($monster->xp, $character->xp);
    }

    public function testShouldReceiveFullXpWhenTrainingASkillThatIsMaxLevel() {
        $character = $this->characterFactory->getCharacter();

        $accuracySkill = GameSkill::where('name', 'Accuracy')->first();

        $character->skills()->where('game_skill_id', $accuracySkill->id)->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
            'level' => $accuracySkill->max_level
        ]);

        $character = $character->refresh();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp'          => 50,
            'max_level'   => 9999,
        ]);

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->refresh()->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity' => 1602801731,
        ]]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);

        $character = $character->refresh();

        $this->assertEquals($monster->xp, $character->xp);
    }

    public function testShouldUpdateCharacterCurrenciesWhenLoggedIn() {
        $character = $this->characterFactory->getCharacter();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->refresh()->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity' => 1602801731,
        ]]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function testBattleItemRewardHandlerIsDispatched() {
        $character = $this->characterFactory->getCharacter();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        Queue::assertPushed(BattleItemHandler::class);
    }

    public function testShouldGetFactionPoints() {
        $character = $this->characterFactory->assignFactionSystem()->getCharacter();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        $character = $character->refresh();

        $faction = $character->factions()->where('game_map_id', $character->map->game_map_id)->first();

        $this->assertNotNull($faction);

        $this->assertGreaterThan(0, $faction->current_points);
    }

    public function testShouldNotUpdateGlobalEventParticipationWhenNoEventIsRunning() {
        $character = $this->characterFactory->getCharacter();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function testShouldNotUpdateGlobalEventParticipationWhenNoGlobalEventIsRunning() {
        $character = $this->characterFactory->getCharacter();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT
        ]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        $character = $character->refresh();

        $this->assertNull($character->globalEventParticipation);
    }

    public function testShouldUpdateGlobalEventParticipation() {
        $character = $this->characterFactory->getCharacter();

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createEvent([
            'type' => EventType::WINTER_EVENT
        ]);

        $this->createGlobalEventGoal([
            'event_type'                      => EventType::WINTER_EVENT,
            'item_specialty_type_reward'      => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type'                     => RandomAffixDetails::LEGENDARY,
        ]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventParticipation);
    }

    public function testNoFactionRewardsGivenWhenCharacterIsInPurgatory() {
        $character = $this->characterFactory->assignFactionSystem()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::PURGATORY
            ])->id
        ]);

        $monster   = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Event::fake();

        Queue::fake();

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        $character = $character->refresh();

        foreach ($character->factions as $faction) {
            $this->assertEquals(0, $faction->current_points);
        }
    }
}
