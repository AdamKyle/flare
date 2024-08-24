<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Skills\Handlers\UpdateItemSkill;
use Facades\App\Game\Skills\Handlers\UpdateItemSkill as UpdateItemSkillFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class SecondaryRewardServiceTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateMonster, RefreshDatabase;

    private ?SecondaryRewardService $secondaryRewardService;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->secondaryRewardService = resolve(SecondaryRewardService::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->secondaryRewardService = null;

        $this->characterFactory = null;
    }

    public function testUpdateTopBarIsNotCalledWhenNotLoggedIn()
    {
        $character = $this->characterFactory->getCharacter();

        Event::fake();

        $this->secondaryRewardService->handleSecondaryRewards($character);

        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testUpdateTopBarIsCalledWhenLoggedIn()
    {
        $character = $this->characterFactory->getCharacter();

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id' => '1',
            'user_id' => $character->user->id,
            'ip_address' => '1',
            'user_agent' => '1',
            'payload' => '1',
            'last_activity' => 1602801731,
        ]]);

        Event::fake();

        $this->secondaryRewardService->handleSecondaryRewards($character);

        Event::assertDispatched(UpdateTopBarEvent::class);
    }

    public function testItemSkillDoNotsGetUpdated()
    {
        $mock = Mockery::mock(UpdateItemSkill::class);

        $this->app->instance(UpdateItemSkill::class, $mock);

        $character = $this->characterFactory->equipStartingEquipment()->getCharacter();

        Event::fake();

        $this->secondaryRewardService->handleSecondaryRewards($character);

        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $mock->shouldNotReceive('updateItemSkill');
    }

    public function testItemSkillsGetUpdated()
    {
        UpdateItemSkillFacade::shouldReceive('updateItemSkill')->once()->andReturn(null);

        $item = $this->createItem(['type' => 'artifact']);

        $character = $this->characterFactory->inventoryManagement()->giveItem($item)->equipArtifact($item->name)->getCharacter();

        Event::fake();

        $this->secondaryRewardService->handleSecondaryRewards($character);

        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }
}
