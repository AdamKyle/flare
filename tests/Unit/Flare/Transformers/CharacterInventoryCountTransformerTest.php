<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\GemBagSlot;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class CharacterInventoryCountTransformerTest extends TestCase
{
    use CreateGem, CreateItem, RefreshDatabase;

    public function testTransformerPayloadIncludesMainInventoryKeys(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $data = resolve(CharacterInventoryCountTransformer::class)->transform($character);

        $this->assertArrayHasKey('inventory_max', $data);
        $this->assertArrayHasKey('inventory_count', $data);
        $this->assertArrayHasKey('inventory_bag_count', $data);
    }

    public function testTransformerPayloadIncludesAlchemyBagKeys(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $data = resolve(CharacterInventoryCountTransformer::class)->transform($character);

        $this->assertArrayHasKey('alchemy_bag_count', $data);
        $this->assertArrayHasKey('alchemy_bag_limit', $data);
        $this->assertArrayHasKey('is_alchemy_bag_full', $data);
    }

    public function testTransformerPayloadIncludesGemBagKeys(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $data = resolve(CharacterInventoryCountTransformer::class)->transform($character);

        $this->assertArrayHasKey('gem_bag_count', $data);
        $this->assertArrayHasKey('gem_bag_limit', $data);
        $this->assertArrayHasKey('is_gem_bag_full', $data);
    }

    public function testTransformerAlchemyBagCountReflectsSlotAmounts(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $data = resolve(CharacterInventoryCountTransformer::class)->transform($character->refresh());

        $this->assertEquals(5, $data['alchemy_bag_count']);
        $this->assertEquals(150, $data['alchemy_bag_limit']);
        $this->assertFalse($data['is_alchemy_bag_full']);
    }

    public function testTransformerGemBagCountReflectsSlotAmounts(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        GemBagSlot::create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 4,
        ]);

        $data = resolve(CharacterInventoryCountTransformer::class)->transform($character->refresh());

        $this->assertEquals(4, $data['gem_bag_count']);
        $this->assertEquals(150, $data['gem_bag_limit']);
        $this->assertFalse($data['is_gem_bag_full']);
    }

    public function testTransformerIsAlchemyBagFullWhenAtLimit(): void
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

        $data = resolve(CharacterInventoryCountTransformer::class)->transform($character->refresh());

        $this->assertTrue($data['is_alchemy_bag_full']);
    }

    public function testTransformerIsGemBagFullWhenAtLimit(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gem_bag_limit' => 2]);
        $character = $character->refresh();

        GemBagSlot::create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 2,
        ]);

        $data = resolve(CharacterInventoryCountTransformer::class)->transform($character->refresh());

        $this->assertTrue($data['is_gem_bag_full']);
    }
}
