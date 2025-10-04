<?php

namespace Tests\Unit\Flare\Items\Comparison;

use App\Flare\Items\Comparison\ItemComparison;
use App\Flare\Items\Values\ArmourType;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemComparisonTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $characterFactory = null;

    private ?ItemComparison $itemComparison = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->itemComparison = $this->app->make(ItemComparison::class);
    }

    protected function tearDown(): void
    {
        $this->characterFactory = null;
        $this->itemComparison = null;

        parent::tearDown();
    }

    public function test_returns_empty_when_no_equip_positions(): void
    {
        $character = $this->characterFactory->getCharacter()->refresh();

        $unknownTypeItem = $this->createItem([
            'type' => 'artifact',
            'name' => 'Mystery Rock',
            'description' => '???',
        ]);

        $rows = $this->itemComparison->fetchDetails($unknownTypeItem, $character->inventory->slots, $character);

        $this->assertSame([], $rows);
    }

    public function test_returns_empty_when_no_matching_slots(): void
    {
        $character = $this->characterFactory->getCharacter()->refresh();

        $ring = $this->createItem([
            'type' => 'ring',
            'name' => 'Empty Finger',
            'description' => 'Lonely.',
        ]);

        $rows = $this->itemComparison->fetchDetails($ring, $character->inventory->slots, $character);

        $this->assertSame([], $rows);
    }

    public function test_spells_compare_against_both_slots_and_reverse_order(): void
    {
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => SpellTypes::DAMAGE, 'base_damage' => 5, 'name' => 'Equipped Spell One']), true, 'spell-one')
            ->giveItem($this->createItem(['type' => SpellTypes::DAMAGE, 'base_damage' => 7, 'name' => 'Equipped Spell Two']), true, 'spell-two')
            ->getCharacter()
            ->refresh();

        $candidateSpell = $this->createItem([
            'type' => SpellTypes::DAMAGE,
            'base_damage' => 10,
            'name' => 'Candidate Spell',
            'description' => 'Boom',
        ]);

        $rows = $this->itemComparison->fetchDetails($candidateSpell, $character->inventory->slots, $character);

        $this->assertCount(2, $rows);
        $this->assertSame('spell-one', $rows[0]['position']);
        $this->assertSame('spell-two', $rows[1]['position']);

        $this->assertArrayHasKey('comparison', $rows[0]);
        $this->assertArrayHasKey('adjustments', $rows[0]['comparison']);
        $this->assertArrayHasKey('equipped_item', $rows[0]);
        $this->assertArrayHasKey('name', $rows[0]['equipped_item']);
    }

    public function test_two_handed_weapon_types_compare_against_both_hands_and_reverse_order(): void
    {
        $twoHandedTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::BOW,
            WeaponTypes::HAMMER,
        ];

        foreach ($twoHandedTypes as $twoHandedType) {
            $character = $this->characterFactory->inventoryManagement()
                ->giveItem($this->createItem(['type' => $twoHandedType, 'base_damage' => 10, 'name' => 'Left Equipped '.$twoHandedType]), true, 'left-hand')
                ->giveItem($this->createItem(['type' => $twoHandedType, 'base_damage' => 12, 'name' => 'Right Equipped '.$twoHandedType]), true, 'right-hand')
                ->getCharacter()
                ->refresh();

            $candidateItem = $this->createItem([
                'type' => $twoHandedType,
                'base_damage' => 15,
                'name' => 'Candidate '.$twoHandedType,
                'description' => 'Test',
            ]);

            $rows = $this->itemComparison->fetchDetails($candidateItem, $character->inventory->slots, $character);

            $this->assertCount(2, $rows, 'Unexpected row count for type: '.$twoHandedType);
            $this->assertSame('left-hand', $rows[0]['position'], 'First pos mismatch for type: '.$twoHandedType);
            $this->assertSame('right-hand', $rows[1]['position'], 'Second pos mismatch for type: '.$twoHandedType);

            $this->assertArrayHasKey('comparison', $rows[0]);
            $this->assertArrayHasKey('adjustments', $rows[0]['comparison']);
            $this->assertArrayHasKey('equipped_item', $rows[0]);
        }
    }

    public function test_shield_compares_against_both_hands(): void
    {
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'shield', 'base_ac' => 5, 'name' => 'Left Shield']), true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'shield', 'base_ac' => 7, 'name' => 'Right Shield']), true, 'right-hand')
            ->getCharacter()
            ->refresh();

        $candidateShield = $this->createItem([
            'type' => 'shield',
            'base_ac' => 10,
            'name' => 'Candidate Shield',
            'description' => 'Blocky',
        ]);

        $rows = $this->itemComparison->fetchDetails($candidateShield, $character->inventory->slots, $character);

        $this->assertCount(2, $rows);
        $this->assertSame('left-hand', $rows[0]['position']);
        $this->assertSame('right-hand', $rows[1]['position']);
    }

    public function test_rings_compare_against_both_ring_slots(): void
    {
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring One']), true, 'ring-one')
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring Two']), true, 'ring-two')
            ->getCharacter()
            ->refresh();

        $candidateRing = $this->createItem([
            'type' => 'ring',
            'name' => 'Candidate Ring',
            'description' => 'Shiny',
        ]);

        $rows = $this->itemComparison->fetchDetails($candidateRing, $character->inventory->slots, $character);

        $this->assertCount(2, $rows);
        $this->assertSame('ring-one', $rows[0]['position']);
        $this->assertSame('ring-two', $rows[1]['position']);
    }

    public function test_armour_type_resolves_mapped_positions(): void
    {
        $map = ArmourType::getArmourPositions();
        $chosenType = null;
        $chosenFirstSlot = null;

        foreach ($map as $type => $positions) {
            if ($type === 'shield') {
                continue;
            }
            if (is_array($positions) && ! empty($positions)) {
                $chosenType = $type;
                $chosenFirstSlot = $positions[0];
                break;
            }
        }

        $equippedItem = $this->createItem(['type' => $chosenType, 'name' => 'Equipped Armour']);
        $candidate = $this->createItem(['type' => $chosenType, 'name' => 'Candidate Armour', 'description' => 'sample']);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($equippedItem, true, $chosenFirstSlot)
            ->getCharacter()
            ->refresh();

        $rows = $this->itemComparison->fetchDetails($candidate, $character->inventory->slots, $character);

        $this->assertCount(1, $rows);
        $this->assertSame($chosenFirstSlot, $rows[0]['position']);
        $this->assertArrayHasKey('comparison', $rows[0]);
        $this->assertArrayHasKey('adjustments', $rows[0]['comparison']);
        $this->assertArrayHasKey('equipped_item', $rows[0]);
    }

    public function test_comparison_row_includes_slot_flags_and_equipped_details_block(): void
    {
        $equipped = $this->createItem(['type' => WeaponTypes::STAVE, 'base_damage' => 5, 'name' => 'Equipped Staff']);
        $candidate = $this->createItem(['type' => WeaponTypes::STAVE, 'base_damage' => 12, 'name' => 'Candidate Staff']);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($equipped, true, 'left-hand')
            ->getCharacter()
            ->refresh();

        $rows = $this->itemComparison->fetchDetails($candidate, $character->inventory->slots, $character);

        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertArrayHasKey('equipped_item', $row);
        $this->assertArrayHasKey('affix_count', $row['equipped_item']);
        $this->assertArrayHasKey('max_holy_stacks', $row['equipped_item']);
        $this->assertArrayHasKey('holy_stacks_applied', $row['equipped_item']);
        $this->assertArrayHasKey('holy_stacks_total_stat_increase', $row['equipped_item']);
        $this->assertArrayHasKey('is_cosmic', $row['equipped_item']);
        $this->assertArrayHasKey('is_mythic', $row['equipped_item']);
        $this->assertArrayHasKey('is_unique', $row['equipped_item']);
        $this->assertArrayHasKey('usable', $row['equipped_item']);
        $this->assertArrayHasKey('holy_level', $row['equipped_item']);
        $this->assertArrayHasKey('damages_kingdoms', $row['equipped_item']);
        $this->assertArrayHasKey('name', $row['equipped_item']);
        $this->assertArrayHasKey('description', $row['equipped_item']);
        $this->assertArrayHasKey('type', $row['equipped_item']);
    }

    public function test_no_matching_slots_even_with_unrelated_items_equipped(): void
    {
        $equippedStave = $this->createItem(['type' => WeaponTypes::STAVE, 'name' => 'Equipped Staff']);
        $candidateRing = $this->createItem(['type' => 'ring', 'name' => 'Candidate Ring']);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($equippedStave, true, 'left-hand')
            ->getCharacter()
            ->refresh();

        $rows = $this->itemComparison->fetchDetails($candidateRing, $character->inventory->slots, $character);

        $this->assertSame([], $rows);
    }

    public function test_ignores_unequipped_slots_even_if_positions_match(): void
    {
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring One']), false, 'ring-one')
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring Two']), false, 'ring-two')
            ->getCharacter()
            ->refresh();

        $candidateRing = $this->createItem([
            'type' => 'ring',
            'name' => 'Candidate Ring',
        ]);

        $rows = $this->itemComparison->fetchDetails($candidateRing, $character->inventory->slots, $character);

        $this->assertSame([], $rows);
    }
}
