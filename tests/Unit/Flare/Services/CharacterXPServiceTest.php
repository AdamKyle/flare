<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Services\CharacterXPService;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\NpcTypes;
use App\Game\Kingdoms\Service\KingdomUpdateService;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateNpc;

class CharacterXPServiceTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?CharacterXPService $characterXPService;

    public function setUp(): void {
        parent::setUp();

        $this->character          = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->characterXPService = new CharacterXPService();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character          = null;
        $this->characterXPService = null;
    }

    public function testGetXpValue() {
        $xp = $this->characterXPService->determineXPToAward($this->character->getCharacter(), 10);

        $this->assertEquals(10, $xp);
    }

    public function testGetNoXpValue() {
        $xp = $this->characterXPService->determineXPToAward($this->character->getCharacter(), 0);

        $this->assertEquals(0, $xp);
    }

    public function testGetHalfWayXpValue() {

        $character = $this->character->getCharacter();

        $character->update(['level' => 500]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.75), $xp);
    }

    public function testGetThreeQuartersWayXpValue() {

        $character = $this->character->getCharacter();

        $character->update(['level' => 750]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.50), $xp);
    }

    public function testGetLastLegXP() {

        $character = $this->character->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.25), $xp);
    }

    public function testGetLastLegXPWithItemThatIgnoresCaps() {

        $item = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => true
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 + 10 * 0.50), $xp);
    }

    public function testGetLastLegXPWithItemThatDoesNotIgnoresCaps() {

        $item = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => false
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function testGetLastLegXPWithItemsThatDoesAndDoesNotIgnoresCaps() {

        $itemDoesNotIgnoreCaps = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => false
        ]);

        $itemDoesIgnoreCaps = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => true
        ]);

        $character = $this->character->inventoryManagement()->giveItem($itemDoesNotIgnoreCaps);
        $character = $character->giveItem($itemDoesIgnoreCaps)->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function testGetZeroXPWhenCannotLevelAnyFurther() {

        $character = $this->character->getCharacter();

        $character->update(['level' => 1000]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(0, $xp);
    }

    public function testCanContinueLeveling() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(10, $xp);
    }

    public function testCanContinueLevelingWithItemThatIgnoresCaps() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        $itemIgnoresCaps = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => true
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item);
        $character = $character->giveItem($itemIgnoresCaps)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function testCanContinueLevelingWithItemThatDoesIgnoresCaps() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        $itemDoesNotIgnoresCaps = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => false
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item);
        $character = $character->giveItem($itemDoesNotIgnoresCaps)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function testCanContinueLevelingWithItemThatDoesAndDoesNotIgnoresCaps() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        $itemDoesNotIgnoresCaps = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => false
        ]);

        $itemIgnoresCaps = $this->createItem([
            'type'         => 'quest',
            'xp_bonus'     => 0.50,
            'ignores_caps' => true
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item);
        $character = $character->giveItem($itemIgnoresCaps);
        $character = $character->giveItem($itemDoesNotIgnoresCaps)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function testCanContinueLevelingHalfWayMark() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1500]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.75), $xp);
    }

    public function testCanContinueLevelingThreeQuartersMark() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2250]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.50), $xp);
    }

    public function testCanContinueLevelingLastLegMark() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.25), $xp);
    }

    public function testCanContinueLevelingGetNoXpWhenMax() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 3000]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(10, $xp);
    }

    public function testContinueLevelingWithNoConfig() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(0, $xp);
    }

    public function testCharacterCanGainXP() {
        $character = $this->character->getCharacter();

        $this->assertTrue($this->characterXPService->canCharacterGainXP($character));
    }

    public function testCharacterWhoCanContinueLevelingGainsXP() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $character = $character->refresh();

        $this->assertTrue($this->characterXPService->canCharacterGainXP($character));
    }

    public function testCharacterCannotGainXp() {
        $character = $this->character->getCharacter();

        $character->update(['level' => 1000]);

        $character = $character->refresh();

        $this->assertFalse($this->characterXPService->canCharacterGainXP($character));
    }

    public function testCharacterWhoCanContinueLevelingCannotGainXPWhenNoConfig() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $character = $character->refresh();

        $this->assertFalse($this->characterXPService->canCharacterGainXP($character));
    }

    public function testCharacterWhoCanContinueLevelingCannotGainXP() {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type'   => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level'      => 3000,
            'half_way'       => 1500,
            'three_quarters' => 2250,
            'last_leg'       => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 3000]);

        $character = $character->refresh();

        $this->assertFalse($this->characterXPService->canCharacterGainXP($character));
    }
}
