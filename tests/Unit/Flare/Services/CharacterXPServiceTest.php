<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Services\CharacterXPService;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterXPServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterXPService $characterXPService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->characterXPService = new CharacterXPService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterXPService = null;
    }

    public function test_get_xp_value()
    {
        $xp = $this->characterXPService->determineXPToAward($this->character->getCharacter(), 10);

        $this->assertEquals(10, $xp);
    }

    public function test_get_no_xp_value()
    {
        $xp = $this->characterXPService->determineXPToAward($this->character->getCharacter(), 0);

        $this->assertEquals(0, $xp);
    }

    public function test_get_half_way_xp_value()
    {

        $character = $this->character->getCharacter();

        $character->update(['level' => 500]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.75), $xp);
    }

    public function test_get_three_quarters_way_xp_value()
    {

        $character = $this->character->getCharacter();

        $character->update(['level' => 750]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.50), $xp);
    }

    public function test_get_last_leg_xp()
    {

        $character = $this->character->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.25), $xp);
    }

    public function test_get_last_leg_xp_with_item_that_ignores_caps()
    {

        $item = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 + 10 * 0.50), $xp);
    }

    public function test_get_last_leg_xp_with_item_that_does_not_ignores_caps()
    {

        $item = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => false,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function test_get_last_leg_xp_with_items_that_does_and_does_not_ignores_caps()
    {

        $itemDoesNotIgnoreCaps = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => false,
        ]);

        $itemDoesIgnoreCaps = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($itemDoesNotIgnoreCaps);
        $character = $character->giveItem($itemDoesIgnoreCaps)->getCharacter();

        $character->update(['level' => 900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function test_get_zero_xp_when_cannot_level_any_further()
    {

        $character = $this->character->getCharacter();

        $character->update(['level' => 1000]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(0, $xp);
    }

    public function test_can_continue_leveling()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(10, $xp);
    }

    public function test_can_continue_leveling_with_item_that_ignores_caps()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        $itemIgnoresCaps = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => true,
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item);
        $character = $character->giveItem($itemIgnoresCaps)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function test_can_continue_leveling_with_item_that_does_ignores_caps()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        $itemDoesNotIgnoresCaps = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => false,
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item);
        $character = $character->giveItem($itemDoesNotIgnoresCaps)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function test_can_continue_leveling_with_item_that_does_and_does_not_ignores_caps()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        $itemDoesNotIgnoresCaps = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => false,
        ]);

        $itemIgnoresCaps = $this->createItem([
            'type' => 'quest',
            'xp_bonus' => 0.50,
            'ignores_caps' => true,
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item);
        $character = $character->giveItem($itemIgnoresCaps);
        $character = $character->giveItem($itemDoesNotIgnoresCaps)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertGreaterThan(0, $xp);
    }

    public function test_can_continue_leveling_half_way_mark()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1500]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.75), $xp);
    }

    public function test_can_continue_leveling_three_quarters_mark()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2250]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.50), $xp);
    }

    public function test_can_continue_leveling_last_leg_mark()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2900]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(ceil(10 * 0.25), $xp);
    }

    public function test_continue_leveling_with_no_config()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 10);

        $this->assertEquals(0, $xp);
    }

    public function test_character_can_gain_xp()
    {
        $character = $this->character->getCharacter();

        $this->assertTrue($this->characterXPService->canCharacterGainXP($character));
    }

    public function test_character_who_can_continue_leveling_gains_xp()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $character = $character->refresh();

        $this->assertTrue($this->characterXPService->canCharacterGainXP($character));
    }

    public function test_character_cannot_gain_xp()
    {
        $character = $this->character->getCharacter();

        $character->update(['level' => 1000]);

        $character = $character->refresh();

        $this->assertFalse($this->characterXPService->canCharacterGainXP($character));
    }

    public function test_character_who_can_continue_leveling_cannot_gain_xp_when_no_config()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 1000]);

        $character = $character->refresh();

        $this->assertFalse($this->characterXPService->canCharacterGainXP($character));
    }

    public function test_character_who_can_continue_leveling_cannot_gain_xp()
    {
        $item = $this->createItem([
            'effect' => ItemEffectsValue::CONTINUE_LEVELING,
            'type' => 'quest',
        ]);

        MaxLevelConfiguration::create([
            'max_level' => 3000,
            'half_way' => 1500,
            'three_quarters' => 2250,
            'last_leg' => 2900,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 3000]);

        $character = $character->refresh();

        $this->assertFalse($this->characterXPService->canCharacterGainXP($character));
    }
}
