<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Transformers\InventorySetOptionTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;

class InventorySetOptionTransformerTest extends TestCase
{
    use CreateInventorySets, RefreshDatabase;

    private ?InventorySetOptionTransformer $transformer;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = resolve(InventorySetOptionTransformer::class);
        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->transformer = null;
        $this->character = null;
    }

    public function test_transform_exposes_only_the_lean_fields(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'name' => 'Lean Set', 'is_equipped' => false, 'max_slots' => 10]);
        $set->setAttribute('slots_count', 3);
        $set->setAttribute('set_number', 4);

        $data = $this->transformer->transform($set);

        $this->assertSame([
            'set_id' => $set->id,
            'name' => 'Lean Set',
            'equipped' => false,
            'is_batch_crafting_set' => false,
            'max_slots' => 10,
            'current_slots' => 3,
            'remaining_slots' => 7,
            'set_number' => 4,
            'display_name' => 'Lean Set',
        ], $data);
    }

    public function test_transform_reports_the_batch_crafting_set_truthfully(): void
    {
        $set = $this->createInventorySet([
            'character_id' => $this->character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
            'is_equipped' => false,
        ]);
        $set->setAttribute('slots_count', 0);
        $set->setAttribute('set_number', null);

        $data = $this->transformer->transform($set);

        $this->assertTrue($data['is_batch_crafting_set']);
        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $data['display_name']);
    }

    public function test_transform_uses_the_normal_set_ordinal_as_the_fallback_display_name_when_unnamed(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'name' => null, 'is_equipped' => false, 'max_slots' => 10]);
        $set->setAttribute('slots_count', 0);
        $set->setAttribute('set_number', 7);

        $data = $this->transformer->transform($set);

        $this->assertNull($data['name']);
        $this->assertSame(7, $data['set_number']);
        $this->assertSame('Set 7', $data['display_name']);
    }

    public function test_transform_reports_null_remaining_slots_when_the_set_has_no_capacity_limit(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'name' => 'Unlimited Set', 'is_equipped' => false, 'max_slots' => null]);
        $set->setAttribute('slots_count', 5);

        $data = $this->transformer->transform($set);

        $this->assertNull($data['remaining_slots']);
    }
}
