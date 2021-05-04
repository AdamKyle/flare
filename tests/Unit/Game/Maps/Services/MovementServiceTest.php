<?php

namespace Tests\Unit\Game\Maps\Services;

use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Location;
use App\Game\Maps\Services\MovementService;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;

class MovementServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateLocation;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->updateCharacter([
                                                    'inventory_max' => 1,
                                                 ])
                                                 ->givePlayerLocation()
                                                 ->equipStartingEquipment()
                                                 ->kingdomManagement()
                                                 ->assignKingdom();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanManageOwnKingdom() {
        $movementService = resolve(MovementService::class);

        $movementService->processArea($this->character->getCharacter());

        $data = $movementService->kingdomDetails();

        $this->assertTrue($data['can_manage']);
    }

    public function testCanAttackKingdom() {
        $movementService = resolve(MovementService::class);

        $character = $this->character->getCharacter();

        $secondCharacter = $this->createEnemyKingdom()->getCharacter();

        $character->map()->update([
            'character_position_x' => $secondCharacter->map->character_position_x,
            'character_position_y' => $secondCharacter->map->character_position_y,
        ]);

        $character = $character->refresh();

        $movementService->processArea($character);

        $data = $movementService->kingdomDetails();

        $this->assertFalse($data['can_manage']);
        $this->assertNotEmpty($data['kingdom_to_attack']);
        $this->assertTrue($data['can_attack']);
    }

    public function testCannotGetQuestItemInventoryFull() {
        $location  = $this->createTestLocation();
        $character = $this->character->getCharacter();

        $movementService = resolve(MovementService::class);

        $movementService->processLocation($location, $character);

        $item = $character->inventory->slots->filter(function($slot) use ($location) {
            return $slot->item_id === $location->questRewardItem->id;
        })->first();

        $this->assertNull($item);
    }

    protected function createTestLocation(): Location {
        $location = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $item = $location->questRewardItem()->create([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $location->update([
            'quest_reward_item_id' => $item->id,
        ]);

        return $location->refresh();
    }

    protected function createEnemyKingdom(): KingdomManagement {
        return (new CharacterFactory)->createBaseCharacter()
                                     ->givePlayerLocation(32, 32)
                                     ->kingdomManagement()
                                     ->assignKingdom([
                                        'x_position'  => 32,
                                        'y_position' => 32,
                                    ]);
    }
}
