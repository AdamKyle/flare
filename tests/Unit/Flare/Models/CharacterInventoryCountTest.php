<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\AlchemyBagSlot;
use App\Game\Character\CharacterInventory\Services\CurrencyCacheGenerator;
use App\Game\Core\Currency\Values\CurrencyCacheType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateGemBagSlot;
use Tests\Traits\CreateItem;

class CharacterInventoryCountTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateGemBagSlot, CreateItem, RefreshDatabase;

    public function test_inventory_count_excludes_alchemy_items(): void
    {
        $regularItem = $this->createItem(['type' => 'weapon']);
        $alchemyItem = $this->createItem(['type' => 'alchemy']);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($regularItem)
            ->giveItem($alchemyItem)
            ->getCharacter();

        $this->assertEquals(1, $character->getInventoryCount());
    }

    public function test_inventory_count_excludes_quest_items(): void
    {
        $regularItem = $this->createItem(['type' => 'weapon']);
        $questItem = $this->createItem(['type' => 'quest']);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($regularItem)
            ->giveItem($questItem)
            ->getCharacter();

        $this->assertEquals(1, $character->getInventoryCount());
    }

    public function test_alchemy_bag_count_is_sum_of_slot_amounts(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $alchemyBag = $character->alchemyBag;

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 3,
        ]);

        $this->assertEquals(8, $character->refresh()->getAlchemyBagCount());
    }

    public function test_alchemy_bag_count_excludes_a_compensation_cache(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 3,
        ]);

        (new CurrencyCacheGenerator)->give($character, CurrencyCacheType::GOLD, 500);

        $this->assertEquals(3, $character->refresh()->getAlchemyBagCount());
    }

    public function test_alchemy_bag_count_excludes_multiple_compensation_caches(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        (new CurrencyCacheGenerator)->give($character, CurrencyCacheType::GOLD_DUST, 2_500_000);

        $this->assertEquals(3, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->count());
        $this->assertEquals(0, $character->refresh()->getAlchemyBagCount());
    }

    public function test_ordinary_alchemy_items_fill_the_bag_while_compensation_caches_exist(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 2]);
        $character = $character->refresh();

        (new CurrencyCacheGenerator)->give($character, CurrencyCacheType::SHARDS, 1_500_000);

        $this->assertFalse($character->refresh()->isAlchemyBagFull());

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 2,
        ]);

        $this->assertTrue($character->refresh()->isAlchemyBagFull());
    }

    public function test_gem_bag_count_is_sum_of_slot_amounts(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $gemBag = $character->gemBag;

        $this->createGemBagSlot([
            'gem_bag_id' => $gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 4,
        ]);

        $this->createGemBagSlot([
            'gem_bag_id' => $gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 6,
        ]);

        $this->assertEquals(10, $character->refresh()->getGemBagCount());
    }

    public function test_is_alchemy_bag_full_when_total_equals_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 2]);
        $character = $character->refresh();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 2,
        ]);

        $this->assertTrue($character->refresh()->isAlchemyBagFull());
    }

    public function test_is_alchemy_bag_full_returns_false_when_below_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 10]);

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->refresh()->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->isAlchemyBagFull());
    }

    public function test_is_gem_bag_full_when_total_equals_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 3]);
        $character = $character->refresh();

        $this->createGemBagSlot([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 3,
        ]);

        $this->assertTrue($character->refresh()->isGemBagFull());
    }

    public function test_is_gem_bag_full_returns_false_when_below_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 10]);

        $this->createGemBagSlot([
            'gem_bag_id' => $character->refresh()->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->isGemBagFull());
    }

    public function test_alchemy_bag_count_returns_zero_when_no_bag_exists(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->alchemyBag()->delete();

        $this->assertEquals(0, $character->refresh()->getAlchemyBagCount());
    }

    public function test_gem_bag_count_returns_zero_when_no_bag_exists(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->gemBag()->delete();

        $this->assertEquals(0, $character->refresh()->getGemBagCount());
    }

    public function test_inventory_count_excludes_gem_items(): void
    {
        $regularItem = $this->createItem(['type' => 'weapon']);
        $gemItem = $this->createItem(['type' => 'gem']);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($regularItem)
            ->giveItem($gemItem)
            ->getCharacter();

        $this->assertEquals(1, $character->getInventoryCount());
    }

    public function test_inventory_count_includes_normal_items(): void
    {
        $firstItem = $this->createItem(['type' => 'weapon']);
        $secondItem = $this->createItem(['type' => 'ring']);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($firstItem)
            ->giveItem($secondItem)
            ->getCharacter();

        $this->assertEquals(2, $character->getInventoryCount());
    }

    public function test_can_add_to_alchemy_bag_returns_true_when_adding_would_not_exceed_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 5]);
        $character = $character->refresh();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 4,
        ]);

        $this->assertTrue($character->refresh()->canAddToAlchemyBag(1));
    }

    public function test_can_add_to_alchemy_bag_returns_false_when_adding_would_exceed_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 5]);
        $character = $character->refresh();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->canAddToAlchemyBag(1));
    }

    public function test_can_add_to_gem_bag_returns_true_when_adding_would_not_exceed_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 5]);
        $character = $character->refresh();

        $this->createGemBagSlot([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 4,
        ]);

        $this->assertTrue($character->refresh()->canAddToGemBag(1));
    }

    public function test_can_add_to_gem_bag_returns_false_when_adding_would_exceed_limit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 5]);
        $character = $character->refresh();

        $this->createGemBagSlot([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->canAddToGemBag(1));
    }

    public function test_can_add_to_alchemy_bag_returns_false_for_zero_amount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToAlchemyBag(0));
    }

    public function test_can_add_to_alchemy_bag_returns_false_for_negative_amount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToAlchemyBag(-1));
    }

    public function test_can_add_to_gem_bag_returns_false_for_zero_amount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToGemBag(0));
    }

    public function test_can_add_to_gem_bag_returns_false_for_negative_amount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToGemBag(-1));
    }
}
