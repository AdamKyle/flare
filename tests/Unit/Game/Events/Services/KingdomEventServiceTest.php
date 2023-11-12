<?php

namespace Tests\Unit\Game\Events\Services;


use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;

class KingdomEventServiceTest extends TestCase {

    use RefreshDatabase, CreateGameMap, CreateItem;

    private ?KingdomEventService $kingdomEventService;

    public function setUp(): void {
        parent::setUp();

        $this->kingdomEventService = resolve(KingdomEventService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->kingdomEventService = null;
    }

    public function testGivesPlayerRewardAndDestroysAllKingdoms() {
        $icePlane = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $icePlane)
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $item = $this->createItem(['specialty_type' => ItemSpecialtyType::CORRUPTED_ICE, 'type' => WeaponTypes::HAMMER]);

        Event::fake();

        $this->kingdomEventService->handleKingdomRewardsForEvent(MapNameValue::ICE_PLANE);

        Event::assertDispatched(GlobalMessageEvent::class);

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventory->slots->where('item.specialty_type', ItemSpecialtyType::CORRUPTED_ICE)->all());
        $this->assertEmpty($character->kingdoms);
    }
}
