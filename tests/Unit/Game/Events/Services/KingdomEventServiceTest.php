<?php

namespace Tests\Unit\Game\Events\Services;

use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Maps\Values\MapName;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;

class KingdomEventServiceTest extends TestCase
{
    use CreateGameMap, CreateItem, RefreshDatabase;

    private ?KingdomEventService $kingdomEventService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kingdomEventService = resolve(KingdomEventService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->kingdomEventService = null;
    }

    public function test_gives_player_reward_and_destroys_all_kingdoms()
    {
        $icePlane = $this->createGameMap([
            'name' => MapName::ICE_PLANE->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation(16, 16, $icePlane)
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $item = $this->createItem(['specialty_type' => ItemSpecialtyType::CORRUPTED_ICE->value, 'type' => ItemType::HAMMER->value]);

        Event::fake();

        $this->kingdomEventService->handleKingdomRewardsForEvent(MapName::ICE_PLANE->value);

        Event::assertDispatched(GlobalMessageEvent::class);

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventory->slots->where('item.specialty_type', ItemSpecialtyType::CORRUPTED_ICE->value)->all());
        $this->assertEmpty($character->kingdoms);
    }
}
