<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use Tests\TestCase;

class BatchCraftingTypeTest extends TestCase
{
    public function test_progress_mode_key_returns_the_correct_key_for_every_type(): void
    {
        $this->assertSame('craft_mode', BatchCraftingType::CRAFT->progressModeKey());
        $this->assertSame('craft_enchant_mode', BatchCraftingType::CRAFT_AND_ENCHANT->progressModeKey());
        $this->assertSame('enchant_mode', BatchCraftingType::ENCHANT->progressModeKey());
        $this->assertSame('alchemy_mode', BatchCraftingType::ALCHEMY->progressModeKey());
        $this->assertSame('holy_oils_mode', BatchCraftingType::HOLY_OILS->progressModeKey());
        $this->assertSame('trinketry_mode', BatchCraftingType::TRINKETRY->progressModeKey());
    }

    public function test_mode_from_progress_returns_the_value_at_the_types_progress_mode_key(): void
    {
        $progress = ['craft_mode' => 'specific_item'];

        $this->assertSame('specific_item', BatchCraftingType::CRAFT->modeFromProgress($progress));
    }

    public function test_mode_from_progress_resolves_the_correct_key_for_a_non_craft_type(): void
    {
        $progress = ['trinketry_mode' => 'experience'];

        $this->assertSame('experience', BatchCraftingType::TRINKETRY->modeFromProgress($progress));
    }
}
