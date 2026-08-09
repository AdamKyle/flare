<?php

namespace Tests\Unit\Game\Character\CharacterAttack\Builders;

use App\Game\Character\CharacterAttack\Builders\ClassAttackBuilder;
use App\Game\Character\CharacterAttack\Values\ClassSpecialAttackType;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ClassAttackBuilderTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_fighter_chance_reflects_equipped_sword(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Fighter']);
        $item = $this->createItem(['type' => ItemType::SWORD->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::FIGHTERS_DOUBLE_DAMAGE->value, $data['type']);
        $this->assertSame('Fighter', $data['class_name']);
        $this->assertTrue($data['has_item']);
        $this->assertSame(1, $data['amount']);
    }

    public function test_fighter_chance_is_false_without_a_sword_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Fighter']);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertFalse($data['has_item']);
        $this->assertSame(0, $data['amount']);
    }

    public function test_prophet_chance_reflects_equipped_censer(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Prophet']);
        $item = $this->createItem(['type' => ItemType::CENSER->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::PROPHET_HEALING->value, $data['type']);
        $this->assertSame('Prophet', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_thief_chance_is_true_with_two_daggers_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Thief']);
        $item = $this->createItem(['type' => ItemType::DAGGER->value]);
        $characterFactory->inventoryManagement()->giveItemMultipleTimes($item, 2, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::THIEVES_SHADOW_DANCE->value, $data['type']);
        $this->assertSame('Thief', $data['class_name']);
        $this->assertTrue($data['has_item']);
        $this->assertSame(2, $data['amount']);
    }

    public function test_thief_chance_is_false_with_only_one_dagger_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Thief']);
        $item = $this->createItem(['type' => ItemType::DAGGER->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertFalse($data['has_item']);
        $this->assertSame(1, $data['amount']);
    }

    public function test_heretic_chance_reflects_equipped_wand(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Heretic']);
        $item = $this->createItem(['type' => ItemType::WAND->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::HERETICS_DOUBLE_CAST->value, $data['type']);
        $this->assertSame('Heretic', $data['class_name']);
        $this->assertTrue($data['has_item']);
        $this->assertSame('Cast', $data['attack_type']);
    }

    public function test_ranger_chance_reflects_equipped_bow(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Ranger']);
        $item = $this->createItem(['type' => ItemType::BOW->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::RANGER_TRIPLE_ATTACK->value, $data['type']);
        $this->assertSame('Ranger', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_vampire_chance_reflects_equipped_claw(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Vampire']);
        $item = $this->createItem(['type' => ItemType::CLAW->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::VAMPIRE_THIRST->value, $data['type']);
        $this->assertSame('Vampire', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_blacksmith_chance_reflects_equipped_hammer(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Blacksmith']);
        $item = $this->createItem(['type' => ItemType::HAMMER->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::BLACKSMITHS_HAMMER_SMASH->value, $data['type']);
        $this->assertSame('Blacksmith', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_arcane_alchemist_chance_reflects_equipped_stave(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Arcane Alchemist']);
        $item = $this->createItem(['type' => ItemType::STAVE->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::ARCANE_ALCHEMISTS_DREAMS->value, $data['type']);
        $this->assertSame('Arcane Alchemist', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_prisoner_chance_is_true_with_any_weapon_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Prisoner']);
        $item = $this->createItem(['type' => ItemType::SWORD->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::PRISONER_RAGE->value, $data['type']);
        $this->assertSame('Prisoner', $data['class_name']);
        $this->assertTrue($data['has_item']);
        $this->assertSame(1, $data['amount']);
    }

    public function test_prisoner_chance_is_false_with_no_weapon_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Prisoner']);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertFalse($data['has_item']);
        $this->assertSame(0, $data['amount']);
    }

    public function test_alcoholic_chance_is_true_with_no_weapon_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Alcoholic']);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::ALCOHOLIC_PUKE->value, $data['type']);
        $this->assertSame('Alcoholic', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_alcoholic_chance_is_false_when_any_weapon_type_is_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Alcoholic']);
        $sword = $this->createItem(['type' => ItemType::SWORD->value]);
        $artifact = $this->createItem(['type' => ItemType::ARTIFACT->value]);
        $characterFactory->inventoryManagement()->giveItem($sword, true)->giveItem($artifact, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertFalse($data['has_item']);
    }

    public function test_merchants_place_is_true_with_stave_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Merchant']);
        $item = $this->createItem(['type' => ItemType::STAVE->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::MERCHANTS_SUPPLY->value, $data['type']);
        $this->assertSame('Merchant', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_merchants_place_is_true_with_bow_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Merchant']);
        $item = $this->createItem(['type' => ItemType::BOW->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertTrue($data['has_item']);
    }

    public function test_merchants_place_is_false_without_a_stave_or_bow_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Merchant']);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertFalse($data['has_item']);
        $this->assertSame(0, $data['amount']);
    }

    public function test_gunslinger_chance_reflects_equipped_gun(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Gunslinger']);
        $item = $this->createItem(['type' => ItemType::GUN->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::GUNSLINGERS_ASSASSINATION->value, $data['type']);
        $this->assertSame('Gunslinger', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_sensual_dance_reflects_equipped_fan(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Dancer']);
        $item = $this->createItem(['type' => ItemType::FAN->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::SENSUAL_DANCE->value, $data['type']);
        $this->assertSame('Dancer', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_book_binders_fear_reflects_equipped_scratch_awl(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Book Binder']);
        $item = $this->createItem(['type' => ItemType::SCRATCH_AWL->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::BOOK_BINDERS_FEAR->value, $data['type']);
        $this->assertSame('Scratch Awl', $data['only']);
        $this->assertSame('Book Binder', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_holy_smite_is_true_with_mace_and_shield_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Cleric']);
        $mace = $this->createItem(['type' => ItemType::MACE->value]);
        $shield = $this->createItem(['type' => ArmourType::SHIELD->value]);
        $characterFactory->inventoryManagement()->giveItem($mace, true)->giveItem($shield, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::HOLY_SMITE->value, $data['type']);
        $this->assertSame('Cleric', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_holy_smite_is_false_with_only_a_mace_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Cleric']);
        $mace = $this->createItem(['type' => ItemType::MACE->value]);
        $characterFactory->inventoryManagement()->giveItem($mace, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertFalse($data['has_item']);
    }

    public function test_plague_surge_is_true_with_censer_and_dagger_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Apothecary']);
        $censer = $this->createItem(['type' => ItemType::CENSER->value]);
        $dagger = $this->createItem(['type' => ItemType::DAGGER->value]);
        $characterFactory->inventoryManagement()->giveItem($censer, true)->giveItem($dagger, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::PLAGUE_SURGE->value, $data['type']);
        $this->assertSame('Apothecary', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_plague_surge_is_false_with_only_a_censer_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Apothecary']);
        $censer = $this->createItem(['type' => ItemType::CENSER->value]);
        $characterFactory->inventoryManagement()->giveItem($censer, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertFalse($data['has_item']);
    }

    public function test_beastmaster_chance_prefers_a_bow_when_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Beastmaster']);
        $item = $this->createItem(['type' => ItemType::BOW->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::DEVILS_PIERCING_SHOT->value, $data['type']);
        $this->assertSame('Beastmaster', $data['class_name']);
        $this->assertTrue($data['has_item']);
    }

    public function test_beastmaster_chance_falls_back_to_a_hammer_without_a_bow(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Beastmaster']);
        $item = $this->createItem(['type' => ItemType::HAMMER->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::BEAST_STOMP->value, $data['type']);
        $this->assertTrue($data['has_item']);
    }

    public function test_beastmaster_chance_falls_back_to_a_hammer_and_is_false_without_either(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Beastmaster']);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::BEAST_STOMP->value, $data['type']);
        $this->assertFalse($data['has_item']);
    }

    public function test_buccaneers_chance_is_dual_gun_barrage_with_two_guns_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Buccaneer']);
        $item = $this->createItem(['type' => ItemType::GUN->value]);
        $characterFactory->inventoryManagement()->giveItemMultipleTimes($item, 2, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::BUCCANEERS_DUAL_GUN_BARRAGE->value, $data['type']);
        $this->assertSame('Two Guns', $data['only']);
        $this->assertSame(2, $data['amount']);
        $this->assertTrue($data['has_item']);
    }

    public function test_buccaneers_chance_falls_back_to_gun_and_shield_barrage(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Buccaneer']);
        $gun = $this->createItem(['type' => ItemType::GUN->value]);
        $shield = $this->createItem(['type' => ArmourType::SHIELD->value]);
        $characterFactory->inventoryManagement()->giveItem($gun, true)->giveItem($shield, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::BUCCANEERS_BARRAGE->value, $data['type']);
        $this->assertTrue($data['has_item']);
    }

    public function test_buccaneers_chance_barrage_is_false_with_only_a_gun_equipped(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Buccaneer']);
        $gun = $this->createItem(['type' => ItemType::GUN->value]);
        $characterFactory->inventoryManagement()->giveItem($gun, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertSame(ClassSpecialAttackType::BUCCANEERS_BARRAGE->value, $data['type']);
        $this->assertFalse($data['has_item']);
    }

    public function test_display_only_class_data_includes_class_id_and_weapons(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Fighter']);
        $character = $characterFactory->getCharacter()->refresh();

        $data = (new ClassAttackBuilder($character))->buildAttackData();

        $this->assertSame($character->game_class_id, $data['class_id']);
        $this->assertSame([ItemType::SWORD->value], $data['class_weapons']);
        $this->assertSame('Attack', $data['attack_type']);
    }

    public function test_equipped_class_items_are_listed_when_equipped_weapon_matches_class_type(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Fighter']);
        $item = $this->createItem(['type' => ItemType::SWORD->value]);
        $characterFactory->inventoryManagement()->giveItem($item, true);

        $data = (new ClassAttackBuilder($characterFactory->getCharacter()->refresh()))->buildAttackData();

        $this->assertCount(1, $data['equipped_class_items']);
        $this->assertSame($item->id, $data['equipped_class_items'][0]['item_id']);
        $this->assertSame(ItemType::SWORD->value, $data['equipped_class_items'][0]['type']);
    }

    public function test_equipped_class_items_falls_back_to_equipped_inventory_set(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Fighter']);
        $item = $this->createItem(['type' => ItemType::SWORD->value]);

        $inventorySetManagement = $characterFactory
            ->inventorySetManagement()
            ->createInventorySets(1)
            ->putItemInSet($item, 0, equipped: true);

        $data = (new ClassAttackBuilder($inventorySetManagement->getCharacter()->refresh()))->buildAttackData();

        $this->assertNotEmpty($data['equipped_class_items']);
    }
}
