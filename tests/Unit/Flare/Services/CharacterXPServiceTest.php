<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Services\CharacterXPService;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Services\CharacterService;
use Facades\App\Flare\Calculators\XPCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class CharacterXPServiceTest extends TestCase
{
    use CreateItem, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterXPService $characterXPService;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->characterXPService = resolve(CharacterXPService::class);
    }

    public function tearDown(): void
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

        $characterFactory = $this->character->inventoryManagement()->giveItem($itemDoesNotIgnoreCaps);
        $character = $characterFactory->giveItem($itemDoesIgnoreCaps)->getCharacter();

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

        $characterFactory = $this->character->inventoryManagement()->giveItem($item);
        $character = $characterFactory->giveItem($itemIgnoresCaps)->getCharacter();

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

        $characterFactory = $this->character->inventoryManagement()->giveItem($item);
        $character = $characterFactory->giveItem($itemDoesNotIgnoresCaps)->getCharacter();

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

        $characterFactory = $this->character->inventoryManagement()->giveItem($item);
        $characterFactory = $characterFactory->giveItem($itemIgnoresCaps);
        $character = $characterFactory->giveItem($itemDoesNotIgnoresCaps)->getCharacter();

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

    public function test_set_character_and_get_character()
    {
        $character = $this->character->getCharacter();

        $serviceCharacter = $this->characterXPService->setCharacter($character)->getCharacter();

        $this->assertEquals($character->id, $serviceCharacter->id);
    }

    public function test_handle_level_up_exact_threshold()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'xp' => $character->xp_next,
        ]);

        $character = $character->refresh();

        $this->characterXPService->setCharacter($character)->handleLevelUp();

        $character = $this->characterXPService->getCharacter();

        $this->assertEquals(0, $character->xp);
        $this->assertGreaterThan(1, $character->level);
    }

    public function test_handle_level_up_with_overflow()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'xp' => $character->xp_next + 50,
        ]);

        $character = $character->refresh();

        $this->characterXPService->setCharacter($character)->handleLevelUp();

        $character = $this->characterXPService->getCharacter();

        $this->assertEquals(50, $character->xp);
        $this->assertGreaterThan(1, $character->level);
    }

    public function test_handle_character_level_up_builds_cache_when_forced()
    {
        $character = $this->character->getCharacter();

        $this->characterXPService->setCharacter($character)->handleCharacterLevelUp(0, true);

        $character = $this->characterXPService->getCharacter();

        $this->assertGreaterThan(1, $character->level);
    }

    public function test_distribute_specified_xp_levels_character_up()
    {
        $character = $this->character->getCharacter();

        $xpNext = $character->xp_next;

        $this->characterXPService->setCharacter($character)->distributeSpecifiedXp($xpNext + 50);

        $character = $this->characterXPService->getCharacter();

        $this->assertEquals(50, $character->xp);
        $this->assertGreaterThan(1, $character->level);
    }

    public function test_fetch_xp_for_monster_returns_zero_when_character_cannot_gain_xp()
    {
        $character = $this->character->getCharacter();

        $character->update(['level' => 1000]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'xp' => 500,
            'max_level' => 5000,
        ]);

        $xp = $this->characterXPService->setCharacter($character)->fetchXpForMonster($monster);

        $this->assertEquals(0, $xp);
    }

    public function test_fetch_xp_for_monster_triggers_low_level_monster_message_path()
    {
        $character = $this->character->getCharacter();

        $character->user->update([
            'show_monster_to_low_level_message' => true,
        ]);

        $character->update([
            'level' => 10,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'xp' => 500,
            'max_level' => 5,
        ]);

        $xp = $this->characterXPService->setCharacter($character)->fetchXpForMonster($monster);

        $this->assertIsInt($xp);
        $this->assertGreaterThanOrEqual(0, $xp);
    }

    public function test_fetch_xp_for_monster_adds_guide_quest_bonus()
    {
        $character = $this->character->getCharacter();

        $character->user->update([
            'guide_enabled' => true,
        ]);

        $character->update([
            'level' => 1,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'xp' => 500,
            'max_level' => 5000,
        ]);

        $xp = $this->characterXPService->setCharacter($character)->fetchXpForMonster($monster);

        $this->assertGreaterThanOrEqual(10, $xp);
    }

    public function test_distribute_character_xp_when_not_logged_in_and_logged_in()
    {
        $monster = $this->createMonster([
            'xp' => 500,
            'max_level' => 5000,
        ]);

        $character = $this->character->getCharacter();

        $this->characterXPService->setCharacter($character)->distributeCharacterXP($monster);

        $characterWithSessionFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->createSessionForCharacter();
        $characterWithSession = $characterWithSessionFactory->getCharacter();

        $this->characterXPService->setCharacter($characterWithSession)->distributeCharacterXP($monster);

        $this->assertTrue(true);
    }

    public function test_fetch_xp_for_monster_returns_zero_when_xp_calculator_returns_zero()
    {
        XPCalculator::shouldReceive('fetchXPFromMonster')->once()->andReturn(0);

        $character = $this->character->getCharacter();

        $monster = $this->createMonster([
            'xp' => 500,
            'max_level' => 5000,
        ]);

        $xp = $this->characterXPService->setCharacter($character)->fetchXpForMonster($monster);

        $this->assertEquals(0, $xp);
    }

    public function testStackedXpBoonMultipliesByAmountUsed()
    {
        $character = $this->character->getCharacter();

        $boon = $this->createItem([
            'name' => 'Stacked XP Boon',
            'xp_bonus' => 0.15,
            'can_stack' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $xp = $this->characterXPService->determineXPToAward($character->refresh(), 100);

        $this->assertEquals(160, $xp);
    }

    public function test_xp_next_is_100_when_leveling_to_999(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['level' => 998]);

        $characterService = resolve(CharacterService::class);
        $characterService->levelUpCharacter($character->refresh(), 0);

        $this->assertEquals(100, $character->refresh()->xp_next);
    }

    public function test_xp_next_is_1000_when_leveling_to_1000(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['level' => 999]);

        $characterService = resolve(CharacterService::class);
        $characterService->levelUpCharacter($character->refresh(), 0);

        $this->assertEquals(1000, $character->refresh()->xp_next);
    }

    public function test_xp_next_is_35000_when_leveling_to_4999(): void
    {
        $item = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        MaxLevelConfiguration::create([
            'max_level' => 5000,
            'half_way' => 2500,
            'three_quarters' => 3750,
            'last_leg' => 4800,
        ]);

        $character->update(['level' => 4998]);

        $characterService = resolve(CharacterService::class);
        $characterService->levelUpCharacter($character->refresh(), 0);

        $this->assertEquals(35000, $character->refresh()->xp_next);
    }

    public function test_xp_next_with_reincarnation_penalty_when_leveling_to_4999(): void
    {
        $item = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        MaxLevelConfiguration::create([
            'max_level' => 5000,
            'half_way' => 2500,
            'three_quarters' => 3750,
            'last_leg' => 4800,
        ]);

        $character->update(['level' => 4998, 'xp_penalty' => 5.96]);

        $characterService = resolve(CharacterService::class);
        $characterService->levelUpCharacter($character->refresh(), 0);

        $this->assertEquals(243600, $character->refresh()->xp_next);
    }
}
