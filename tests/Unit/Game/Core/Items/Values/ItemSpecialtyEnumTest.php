<?php

namespace Tests\Unit\Game\Core\Items\Values;

use App\Game\Core\Items\Values\ItemSpecialtyType;
use Tests\TestCase;

class ItemSpecialtyEnumTest extends TestCase
{
    public function test_selection_and_names_preserve_every_specialty(): void
    {
        $this->assertSame([
            'Hell Forged' => 'Hell Forged',
            'Purgatory Chains' => 'Purgatory Chains',
            'Pirate Lord Leather' => 'Pirate Lord Leather',
            'Corrupted Ice' => 'Corrupted Ice',
            'Delusional Silver' => 'Delusional Silver',
            'Twisted Earth' => 'Twisted Earth',
            'Faithless Plate' => 'Faithless Plate',
            'Labyrinth Cloth' => 'Labyrinth Cloth',
        ], ItemSpecialtyType::getValuesForSelect());
        $this->assertSame('Hell Forged', ItemSpecialtyType::HELL_FORGED->getItemSpecialtyTypeName());
    }

    public function test_costs_preserve_paid_and_unpriced_specialties(): void
    {
        $this->assertSame(75_000_000_000, ItemSpecialtyType::PIRATE_LORD_LEATHER->getCost());
        $this->assertSame(75_000_000_000, ItemSpecialtyType::LABYRINTH_CLOTH->getCost());
        $this->assertSame(275_000_000_000, ItemSpecialtyType::CORRUPTED_ICE->getCost());
        $this->assertSame(280_000_000_000, ItemSpecialtyType::DELUSIONAL_SILVER->getCost());
        $this->assertSame(300_000_000_000, ItemSpecialtyType::FAITHLESS_PLATE->getCost());
        $this->assertNull(ItemSpecialtyType::HELL_FORGED->getCost());
    }
}
