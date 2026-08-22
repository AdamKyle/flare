<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Services\HolyOilOilPoolResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateItem;

class HolyOilOilPoolResolverTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?HolyOilOilPoolResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->resolver = resolve(HolyOilOilPoolResolver::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->resolver = null;
    }

    public function test_next_available_oil_resolves_the_first_owned_slot_with_remaining_amount(): void
    {
        $firstOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $firstSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $firstOil->id,
            'amount' => 3,
        ]);
        $secondOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $secondSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $secondOil->id,
            'amount' => 3,
        ]);

        $resolved = $this->resolver->nextAvailableOil($this->character, [$firstSlot->id, $secondSlot->id]);

        $this->assertSame($firstSlot->id, $resolved->id);
    }

    public function test_next_available_oil_preserves_the_characters_selected_order(): void
    {
        $firstOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $firstSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $firstOil->id,
            'amount' => 3,
        ]);
        $secondOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $secondSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $secondOil->id,
            'amount' => 3,
        ]);

        $resolved = $this->resolver->nextAvailableOil($this->character, [$secondSlot->id, $firstSlot->id]);

        $this->assertSame($secondSlot->id, $resolved->id);
    }

    public function test_next_available_oil_skips_an_exhausted_slot_and_resolves_the_next_selected_slot(): void
    {
        $exhaustedOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $exhaustedSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $exhaustedOil->id,
            'amount' => 0,
        ]);
        $availableOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $availableSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $availableOil->id,
            'amount' => 3,
        ]);

        $resolved = $this->resolver->nextAvailableOil($this->character, [$exhaustedSlot->id, $availableSlot->id]);

        $this->assertSame($availableSlot->id, $resolved->id);
    }

    public function test_next_available_oil_returns_null_when_the_entire_pool_is_exhausted(): void
    {
        $exhaustedOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $exhaustedSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $exhaustedOil->id,
            'amount' => 0,
        ]);

        $resolved = $this->resolver->nextAvailableOil($this->character, [$exhaustedSlot->id]);

        $this->assertNull($resolved);
    }

    public function test_next_available_oil_does_not_accept_another_characters_oil_slot(): void
    {
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $otherSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $oil->id,
            'amount' => 3,
        ]);

        $resolved = $this->resolver->nextAvailableOil($this->character, [$otherSlot->id]);

        $this->assertNull($resolved);
    }

    public function test_next_available_oil_returns_null_when_the_character_has_no_alchemy_bag(): void
    {
        $this->character->alchemyBag->delete();
        $this->character = $this->character->refresh();

        $resolved = $this->resolver->nextAvailableOil($this->character, [1]);

        $this->assertNull($resolved);
    }

    public function test_next_available_oil_excludes_a_slot_holding_a_non_holy_oil_item(): void
    {
        $regularItem = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => false]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $regularItem->id,
            'amount' => 3,
        ]);

        $resolved = $this->resolver->nextAvailableOil($this->character, [$slot->id]);

        $this->assertNull($resolved);
    }
}
