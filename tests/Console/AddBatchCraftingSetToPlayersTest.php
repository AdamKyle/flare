<?php

namespace Tests\Console;

use App\Flare\Models\InventorySet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class AddBatchCraftingSetToPlayersTest extends TestCase
{
    use RefreshDatabase;

    public function testDryRunCreatesNothing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players'));

        $this->assertSame(0, $character->inventorySets()->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->count());
    }

    public function testApplyCreatesBatchCraftingSetForCharacters(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $set = $character->inventorySets()->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();

        $this->assertNotNull($set);
        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $set->name);
        $this->assertFalse((bool) $set->can_be_equipped);
        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS, $set->max_slots);
    }

    public function testApplyIsIdempotent(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $this->assertSame(1, $character->inventorySets()->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->count());
    }

    public function testApplyHandlesMoreThanOneHundredCharacters(): void
    {
        for ($index = 0; $index < 101; $index++) {
            (new CharacterFactory)->createBaseCharacter();
        }

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $this->assertSame(101, InventorySet::where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->count());
    }

    public function testConstantBatchCraftingSetNameIsCraftedItemsSet(): void
    {
        $this->assertSame('Crafted Items Set', InventorySet::BATCH_CRAFTING_SET_NAME);
    }

    public function testDryRunDoesNotRenameSetWithOldName(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        InventorySet::create([
            'name' => 'Batch Crafting',
            'character_id' => $character->id,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $this->artisan('batch-crafting:add-set-to-players');

        $set = $character->inventorySets()
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();

        $this->assertSame('Batch Crafting', $set->name);
    }

    public function testApplyRenamesExistingBatchCraftingSpecialSetToCurrentName(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        InventorySet::create([
            'name' => 'Batch Crafting',
            'character_id' => $character->id,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $this->artisan('batch-crafting:add-set-to-players --apply');

        $set = $character->inventorySets()
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();

        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $set->name);
    }

    public function testApplyIsIdempotentAfterRename(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        InventorySet::create([
            'name' => 'Batch Crafting',
            'character_id' => $character->id,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $this->artisan('batch-crafting:add-set-to-players --apply');
        $this->artisan('batch-crafting:add-set-to-players --apply');

        $this->assertSame(
            1,
            $character->inventorySets()->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->count()
        );
    }
}
