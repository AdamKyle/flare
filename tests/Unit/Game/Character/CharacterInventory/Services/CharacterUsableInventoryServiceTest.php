<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterUsableInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;

class CharacterUsableInventoryServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterUsableInventoryService $characterUsableInventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterUsableInventoryService = (new CharacterInventoryServiceFactory)->buildUsableService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->characterUsableInventoryService = null;

    }

    public function test_fetch_character_usable_items_paginates_without_error()
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
        ]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->fetchUsableItems($character);

        $this->assertNotEmpty($result['data']);
        $this->assertSame($item->id, $result['data'][0]['item_id']);
    }

    public function test_get_usable_items_returns_empty_when_character_has_no_alchemy_bag(): void
    {
        $character = $this->character->getCharacter();

        $character->alchemyBag()->delete();

        $result = $this->characterUsableInventoryService->usableItems($character->refresh());

        $this->assertSame([], $result);
    }

    public function test_get_usable_items_filters_by_search_text(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'name' => 'Healing Draught']);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'name' => 'Poison Vial']);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, 'healing');

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_increase_stats(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'increase_stat_by' => 5]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['increase-stats' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_effects_skills(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'increase_skill_bonus_by' => 5]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['effects-skills' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_effects_base_modifiers(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'base_damage_mod' => 0.1]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['effects-base-modifiers' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_damages_kingdoms(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'damages_kingdoms' => true]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['damages-kingdoms' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_holy_oils(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'holy_level' => 1]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['holy-oils' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_scrolls(): void
    {
        $matchingItem = $this->createGemXpScrollItem(0.10);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['scrolls' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_xp_scrolls_subtype_without_the_parent_scrolls_flag(): void
    {
        $matchingItem = $this->createGemXpScrollItem(0.10);
        $otherItem = $this->createGemItemScrollItem(0.02);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['xp-scrolls' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_currency_scrolls_subtype_without_the_parent_scrolls_flag(): void
    {
        $matchingItem = $this->createGemCurrencyScrollItem(0.10);
        $otherItem = $this->createGemXpScrollItem(0.10);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['currency-scrolls' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_item_scrolls_subtype_without_the_parent_scrolls_flag(): void
    {
        $matchingItem = $this->createGemItemScrollItem(0.02);
        $otherItem = $this->createGemCurrencyScrollItem(0.10);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterUsableInventoryService->usableItems($character, '', ['item-scrolls' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }
}
