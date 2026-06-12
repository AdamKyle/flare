<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\GemBagSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class CharacterInventoryCountTest extends TestCase
{
    use CreateGem, CreateItem, RefreshDatabase;

    public function testInventoryCountExcludesAlchemyItems(): void
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

    public function testInventoryCountExcludesQuestItems(): void
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

    public function testAlchemyBagCountIsSumOfSlotAmounts(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $alchemyBag = $character->alchemyBag;

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 3,
        ]);

        $this->assertEquals(8, $character->refresh()->getAlchemyBagCount());
    }

    public function testGemBagCountIsSumOfSlotAmounts(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $gemBag = $character->gemBag;

        GemBagSlot::create([
            'gem_bag_id' => $gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 4,
        ]);

        GemBagSlot::create([
            'gem_bag_id' => $gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 6,
        ]);

        $this->assertEquals(10, $character->refresh()->getGemBagCount());
    }

    public function testIsAlchemyBagFullWhenTotalEqualsLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 2]);
        $character = $character->refresh();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 2,
        ]);

        $this->assertTrue($character->refresh()->isAlchemyBagFull());
    }

    public function testIsAlchemyBagFullReturnsFalseWhenBelowLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 10]);

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->refresh()->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->isAlchemyBagFull());
    }

    public function testIsGemBagFullWhenTotalEqualsLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 3]);
        $character = $character->refresh();

        GemBagSlot::create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 3,
        ]);

        $this->assertTrue($character->refresh()->isGemBagFull());
    }

    public function testIsGemBagFullReturnsFalseWhenBelowLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 10]);

        GemBagSlot::create([
            'gem_bag_id' => $character->refresh()->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->isGemBagFull());
    }

    public function testAlchemyBagCountReturnsZeroWhenNoBagExists(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->alchemyBag()->delete();

        $this->assertEquals(0, $character->refresh()->getAlchemyBagCount());
    }

    public function testGemBagCountReturnsZeroWhenNoBagExists(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->gemBag()->delete();

        $this->assertEquals(0, $character->refresh()->getGemBagCount());
    }

    public function testInventoryCountExcludesGemItems(): void
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

    public function testInventoryCountIncludesNormalItems(): void
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

    public function testCanAddToAlchemyBagReturnsTrueWhenAddingWouldNotExceedLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 5]);
        $character = $character->refresh();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 4,
        ]);

        $this->assertTrue($character->refresh()->canAddToAlchemyBag(1));
    }

    public function testCanAddToAlchemyBagReturnsFalseWhenAddingWouldExceedLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['alchemy_bag_limit' => 5]);
        $character = $character->refresh();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->canAddToAlchemyBag(1));
    }

    public function testCanAddToGemBagReturnsTrueWhenAddingWouldNotExceedLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 5]);
        $character = $character->refresh();

        GemBagSlot::create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 4,
        ]);

        $this->assertTrue($character->refresh()->canAddToGemBag(1));
    }

    public function testCanAddToGemBagReturnsFalseWhenAddingWouldExceedLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 5]);
        $character = $character->refresh();

        GemBagSlot::create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 5,
        ]);

        $this->assertFalse($character->refresh()->canAddToGemBag(1));
    }

    public function testCanAddToAlchemyBagReturnsFalseForZeroAmount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToAlchemyBag(0));
    }

    public function testCanAddToAlchemyBagReturnsFalseForNegativeAmount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToAlchemyBag(-1));
    }

    public function testCanAddToGemBagReturnsFalseForZeroAmount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToGemBag(0));
    }

    public function testCanAddToGemBagReturnsFalseForNegativeAmount(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->assertFalse($character->canAddToGemBag(-1));
    }
}
