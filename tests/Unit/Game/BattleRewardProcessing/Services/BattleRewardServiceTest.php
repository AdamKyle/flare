<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Setup\Character\CharacterFactory;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Jobs\BattleItemHandler;
use Tests\Traits\CreateGameMap;

class BattleRewardServiceTest extends TestCase {

    use CreateMonster, CreateGameMap;

    private ?BattleRewardService $battleRewardService;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void {
        parent::setUp();

        $this->battleRewardService = resolve(BattleRewardService::class);

        $this->characterFactory    = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
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
