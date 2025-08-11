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
    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?CharacterFactory $characterFactory = null;
    private ?ItemComparison $itemComparison = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->itemComparison   = $this->app->make(ItemComparison::class);
    }

    public function tearDown(): void
    {
        $this->characterFactory = null;
        $this->itemComparison   = null;

        parent::tearDown();
    }

    public function testReturnsEmptyWhenNoEquipPositions(): void
    {
        $character = $this->characterFactory->getCharacter()->refresh();

        $unknownTypeItem = $this->createItem([
            'type'        => 'artifact', // not a weapon, not ring/shield/spell, and not in ArmourType map
            'name'        => 'Mystery Rock',
            'description' => '???',
        ]);

        $rows = $this->itemComparison->fetchDetails($unknownTypeItem, $character->inventory->slots, $character);

        $this->assertSame([], $rows);
    }

    public function testReturnsEmptyWhenNoMatchingSlots(): void
    {
        $character = $this->characterFactory->getCharacter()->refresh();

        $ring = $this->createItem([
            'type'        => 'ring',
            'name'        => 'Empty Finger',
            'description' => 'Lonely.',
        ]);

        $rows = $this->itemComparison->fetchDetails($ring, $character->inventory->slots, $character);

        $this->assertSame([], $rows);
    }

    public function testSpellsCompareAgainstBothSlotsAndReverseOrder(): void
    {
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => SpellTypes::DAMAGE, 'base_damage' => 5, 'name' => 'Equipped Spell One']), true, 'spell-one')
            ->giveItem($this->createItem(['type' => SpellTypes::DAMAGE, 'base_damage' => 7, 'name' => 'Equipped Spell Two']), true, 'spell-two')
            ->getCharacter()
            ->refresh();

        $candidateSpell = $this->createItem([
            'type'        => SpellTypes::DAMAGE,
            'base_damage' => 10,
            'name'        => 'Candidate Spell',
            'description' => 'Boom',
        ]);

        $rows = $this->itemComparison->fetchDetails($candidateSpell, $character->inventory->slots, $character);

        $this->assertCount(2, $rows);
        $this->assertSame('spell-two', $rows[0]['position']);
        $this->assertSame('spell-one', $rows[1]['position']);

        $this->assertArrayHasKey('comparison', $rows[0]);
        $this->assertArrayHasKey('adjustments', $rows[0]['comparison']);
        $this->assertSame('Candidate Spell', $rows[0]['comparison']['name']);
    }

    public function testTwoHandedWeaponTypesCompareAgainstBothHandsAndReverseOrder(): void
    {
        $twoHandedTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::BOW,
            WeaponTypes::HAMMER,
        ];

        foreach ($twoHandedTypes as $twoHandedType) {
            $character = $this->characterFactory->inventoryManagement()
                ->giveItem($this->createItem(['type' => $twoHandedType, 'base_damage' => 10, 'name' => 'Left Equipped '.$twoHandedType]),  true, 'left-hand')
                ->giveItem($this->createItem(['type' => $twoHandedType, 'base_damage' => 12, 'name' => 'Right Equipped '.$twoHandedType]), true, 'right-hand')
                ->getCharacter()
                ->refresh();

            $candidateItem = $this->createItem([
                'type'        => $twoHandedType,
                'base_damage' => 15,
                'name'        => 'Candidate '.$twoHandedType,
                'description' => 'Test',
            ]);

            $rows = $this->itemComparison->fetchDetails($candidateItem, $character->inventory->slots, $character);

            $this->assertCount(2, $rows, 'Unexpected row count for type: '.$twoHandedType);
            $this->assertSame('right-hand', $rows[0]['position'], 'First pos mismatch for type: '.$twoHandedType);
            $this->assertSame('left-hand',  $rows[1]['position'], 'Second pos mismatch for type: '.$twoHandedType);

            $this->assertArrayHasKey('comparison', $rows[0]);
            $this->assertArrayHasKey('adjustments', $rows[0]['comparison']);
        }
    }

    public function testShieldComparesAgainstBothHands(): void
    {
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'shield', 'base_ac' => 5, 'name' => 'Left Shield']),  true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'shield', 'base_ac' => 7, 'name' => 'Right Shield']), true, 'right-hand')
            ->getCharacter()
            ->refresh();

        $candidateShield = $this->createItem([
            'type'        => 'shield',
            'base_ac'     => 10,
            'name'        => 'Candidate Shield',
            'description' => 'Blocky',
        ]);

        $rows = $this->itemComparison->fetchDetails($candidateShield, $character->inventory->slots, $character);

        $this->assertCount(2, $rows);
        $this->assertSame('right-hand', $rows[0]['position']);
        $this->assertSame('left-hand',  $rows[1]['position']);
    }

    public function testRingsCompareAgainstBothRingSlots(): void
    {
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring One']), true, 'ring-one')
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring Two']), true, 'ring-two')
            ->getCharacter()
            ->refresh();

        $candidateRing = $this->createItem([
            'type'        => 'ring',
            'name'        => 'Candidate Ring',
            'description' => 'Shiny',
        ]);

        $rows = $this->itemComparison->fetchDetails($candidateRing, $character->inventory->slots, $character);

        $this->assertCount(2, $rows);
        $this->assertSame('ring-two', $rows[0]['position']);
        $this->assertSame('ring-one', $rows[1]['position']);
    }

    public function testArmourTypeResolvesMappedPositions(): void
    {
        $map               = ArmourType::getArmourPositions();
        $chosenType        = null;
        $chosenFirstSlot   = null;

        foreach ($map as $type => $positions) {
            if ($type === 'ring' || $type === 'shield') {
                continue;
            }
            if (is_array($positions) && !empty($positions)) {
                $chosenType      = $type;
                $chosenFirstSlot = $positions[0];
                break;
            }
        }

        $this->assertNotNull($chosenType, 'No suitable armour type found in ArmourType::getArmourPositions()');

        $equippedItem = $this->createItem(['type' => $chosenType, 'name' => 'Equipped Armour']);
        $candidate    = $this->createItem(['type' => $chosenType, 'name' => 'Candidate Armour']);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($equippedItem, true, $chosenFirstSlot)
            ->getCharacter()
            ->refresh();

        $rows = $this->itemComparison->fetchDetails($candidate, $character->inventory->slots, $character);

        $this->assertCount(1, $rows);
        $this->assertSame($chosenFirstSlot, $rows[0]['position']);
        $this->assertArrayHasKey('comparison', $rows[0]);
        $this->assertArrayHasKey('adjustments', $rows[0]['comparison']);
    }

    public function testComparisonRowIncludesSlotFlagsAndEquippedAffixNameKey(): void
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

        $this->assertArrayHasKey('is_unique', $row);
        $this->assertArrayHasKey('is_mythic', $row);
        $this->assertArrayHasKey('is_cosmic', $row);
        $this->assertArrayHasKey('affix_count', $row);
        $this->assertArrayHasKey('holy_stacks_applied', $row);
        $this->assertArrayHasKey('type', $row);
        $this->assertArrayHasKey('equipped_affix_name', $row['comparison']);
    }

    public function testNoMatchingSlotsEvenWithUnrelatedItemsEquipped(): void
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

    public function testIgnoresUnequippedSlotsEvenIfPositionsMatch(): void
    {
        $character = $this->characterFactory->getCharacter()->refresh();

        // Put rings in matching positions, but NOT equipped.
        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring One']),  false, 'ring-one')
            ->giveItem($this->createItem(['type' => 'ring', 'name' => 'Ring Two']),  false, 'ring-two')
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
