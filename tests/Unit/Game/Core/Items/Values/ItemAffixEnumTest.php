<?php

namespace Tests\Unit\Game\Core\Items\Values;

use App\Flare\Models\ItemAffix;
use App\Game\Core\Items\Values\ItemAffixType;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItemAffix;

class ItemAffixEnumTest extends TestCase
{
    use CreateItemAffix, RefreshDatabase;

    public function test_it_preserves_dropdown_labels_and_name_conversion(): void
    {
        $this->assertSame('Base Modifiers', ItemAffixType::dropDownValues()[ItemAffixType::BASE_MODIFIERS->value]);
        $this->assertSame(ItemAffixType::BASE_MODIFIERS->value, ItemAffixType::convertNameToType('Base Modifiers'));
        $this->assertArrayNotHasKey(ItemAffixType::RANDOMLY_GENERATED->value, ItemAffixType::dropDownValues());
    }

    public function test_stat_modifiers_label_retains_existing_conversion_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Stat Modifiers not found for ItemAffixType');

        ItemAffixType::convertNameToType('Stat Modifiers');
    }

    public function test_query_returns_only_item_affixes_matching_the_affix_type(): void
    {
        $matchingAffix = $this->createItemAffix(['affix_type' => ItemAffixType::RANDOMLY_GENERATED->value]);
        $this->createItemAffix(['affix_type' => ItemAffixType::BASE_MODIFIERS->value]);

        $results = ItemAffixType::RANDOMLY_GENERATED->query(ItemAffix::query())->get();

        $this->assertCount(1, $results);
        $this->assertSame($matchingAffix->id, $results->first()->id);
    }

    public function test_invalid_scalar_retains_the_existing_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('99 does not exist on ItemAffixType');

        ItemAffixType::fromValue(99);
    }
}
