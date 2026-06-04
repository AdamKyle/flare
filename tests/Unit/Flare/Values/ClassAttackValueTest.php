<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Items\Values\ItemType;
use App\Flare\Values\ClassAttackValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ClassAttackValueTest extends TestCase
{
    use CreateItem;
    use CreateItemAffix;
    use RefreshDatabase;

    public function test_build_attack_data_contains_legacy_and_display_only_fields_for_equipped_inventory_class_item(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Fighter',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();
        $prefix = $this->createItemAffix([
            'name' => 'Sharp',
            'type' => 'prefix',
            'randomly_generated' => false,
        ]);
        $suffix = $this->createItemAffix([
            'name' => 'Flame',
            'type' => 'suffix',
            'randomly_generated' => false,
        ]);
        $sword = $this->createItem([
            'name' => 'Iron Sword',
            'type' => ItemType::SWORD->value,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($sword, true, 'right-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(0.05, $attackData['chance']);
        $this->assertEquals(ClassAttackValue::FIGHTERS_DOUBLE_DAMAGE, $attackData['type']);
        $this->assertEquals(ItemType::SWORD->value, $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals(1, $attackData['amount']);
        $this->assertEquals($character->game_class_id, $attackData['class_id']);
        $this->assertEquals([ItemType::SWORD->value], $attackData['class_weapons']);
        $this->assertEquals('Attack', $attackData['attack_type']);
        $this->assertEquals([[
            'item_id' => $sword->id,
            'item_name' => '*Sharp* Iron Sword *Flame*',
            'type' => ItemType::SWORD->value,
            'attached_affixes_count' => 2,
            'is_unique' => false,
            'is_mythic' => false,
            'is_cosmic' => false,
            'has_holy_stacks_applied' => 0,
        ]], $attackData['equipped_class_items']);
    }

    public function test_build_attack_data_contains_damage_spell_attack_type_and_active_set_class_items_for_heretic(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Heretic',
                'damage_stat' => 'int',
                'to_hit_stat' => 'focus',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();
        $wand = $this->createItem([
            'name' => 'Ash Wand',
            'type' => ItemType::WAND->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $character = $characterFactory
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($wand, 0, 'right-hand', true)
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::HERETICS_DOUBLE_CAST, $attackData['type']);
        $this->assertEquals(ItemType::WAND->value, $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals(1, $attackData['amount']);
        $this->assertEquals([
            ItemType::WAND->value,
            ItemType::STAVE->value,
            ItemType::SPELL_DAMAGE->value,
        ], $attackData['class_weapons']);
        $this->assertEquals('Cast', $attackData['attack_type']);
        $this->assertEquals([[
            'item_id' => $wand->id,
            'item_name' => 'Ash Wand',
            'type' => ItemType::WAND->value,
            'attached_affixes_count' => 0,
            'is_unique' => false,
            'is_mythic' => false,
            'is_cosmic' => false,
            'has_holy_stacks_applied' => 0,
        ]], $attackData['equipped_class_items']);
    }

    public function test_build_attack_data_contains_healing_spell_attack_type_for_prophet_without_required_item(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Prophet',
                'damage_stat' => 'chr',
                'to_hit_stat' => 'focus',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::PROPHET_HEALING, $attackData['type']);
        $this->assertEquals(ItemType::CENSER->value, $attackData['only']);
        $this->assertFalse($attackData['has_item']);
        $this->assertEquals(0, $attackData['amount']);
        $this->assertEquals([
            ItemType::CENSER->value,
            ItemType::SPELL_HEALING->value,
        ], $attackData['class_weapons']);
        $this->assertEquals('Cast', $attackData['attack_type']);
        $this->assertEquals([], $attackData['equipped_class_items']);
    }
}
