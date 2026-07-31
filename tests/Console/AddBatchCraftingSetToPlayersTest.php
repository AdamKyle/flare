<?php

namespace Tests\Console;

use App\Flare\Models\InventorySet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class AddBatchCraftingSetToPlayersTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_creates_nothing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players'));

        $this->assertSame(0, $character->inventorySets()->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->count());
    }

    public function test_apply_creates_batch_crafting_set_for_characters(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $set = $character->inventorySets()->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();

        $this->assertNotNull($set);
        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $set->name);
        $this->assertFalse((bool) $set->can_be_equipped);
        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS, $set->max_slots);
    }

    public function test_apply_is_idempotent(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $this->assertSame(1, $character->inventorySets()->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->count());
    }

    public function test_apply_handles_more_than_one_hundred_characters(): void
    {
        for ($index = 0; $index < 101; $index++) {
            (new CharacterFactory)->createBaseCharacter();
        }

        $this->assertSame(0, $this->artisan('batch-crafting:add-set-to-players --apply'));

        $this->assertSame(101, InventorySet::where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->count());
    }

    public function test_constant_batch_crafting_set_name_is_crafted_items_set(): void
    {
        $this->assertSame('Crafted Items Set', InventorySet::BATCH_CRAFTING_SET_NAME);
    }

    public function test_dry_run_does_not_rename_set_with_old_name(): void
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

    public function test_apply_renames_existing_batch_crafting_special_set_to_current_name(): void
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

    public function test_apply_is_idempotent_after_rename(): void
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
