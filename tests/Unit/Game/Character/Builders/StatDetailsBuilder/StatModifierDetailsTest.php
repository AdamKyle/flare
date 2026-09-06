<?php

namespace Tests\Unit\Game\Character\Builders\StatDetailsBuilder;

use App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateCharacterClassSpecialitiesEquipped;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;

class StatModifierDetailsTest extends TestCase
{
    use CreateCharacterBoon, CreateCharacterClassSpecialitiesEquipped, CreateGameClassSpecial, CreateGameMap, CreateGameMapGemParamter, CreateGameSkill, CreateGem, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?StatModifierDetails $statModifierDetails;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->statModifierDetails = resolve(StatModifierDetails::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->statModifierDetails = null;
    }

    public function test_for_stat_returns_all_expected_detail_keys(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertSame($character->str, $details['base_value']);
        $this->assertArrayHasKey('modded_value', $details);
        $this->assertArrayHasKey('items_equipped', $details);
        $this->assertArrayHasKey('boon_details', $details);
        $this->assertArrayHasKey('class_specialties', $details);
        $this->assertArrayHasKey('ancestral_item_skill_data', $details);
        $this->assertArrayHasKey('map_reduction', $details);
    }

    public function test_for_stat_includes_equipped_item_affixes(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'str_mod' => 5]);
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertNotEmpty($details['items_equipped']);
        $this->assertSame($prefix->name, $details['items_equipped'][0]['attached_affixes'][0]['name']);
    }

    public function test_for_stat_includes_boon_that_applies_to_all_stats(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['increase_stat_by' => 3]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'amount_used' => 1,
            'item_id' => $item->id,
            'complete' => now()->addHour(),
        ]);

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertArrayHasKey('increases_all_stats', $details['boon_details']);
        $this->assertSame(3.0, $details['boon_details']['increases_all_stats'][0]['increase_amount']);
    }

    public function test_for_stat_includes_boon_that_applies_to_a_specific_stat(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['str_mod' => 5]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'amount_used' => 1,
            'item_id' => $item->id,
            'complete' => now()->addHour(),
        ]);

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertArrayHasKey('increases_single_stat', $details['boon_details']);
        $this->assertSame(5.0, $details['boon_details']['increases_single_stat'][0]['increase_amount']);
    }

    public function test_for_stat_includes_equipped_item_suffix_affix(): void
    {
        $suffix = $this->createItemAffix(['type' => 'suffix', 'str_mod' => 3]);
        $item = $this->createItem(['type' => 'weapon', 'item_suffix_id' => $suffix->id]);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertSame($suffix->name, $details['items_equipped'][0]['attached_affixes'][0]['name']);
    }

    public function test_for_stat_includes_ancestral_item_skill_data_for_equipped_artifact(): void
    {
        $itemSkill = $this->createItemSkill(['str_mod' => 2]);
        $artifact = $this->createItem(['type' => 'artifact']);
        $this->createItemSkillProgression([
            'item_id' => $artifact->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 0,
            'is_training' => false,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($artifact->refresh(), true, 'left_hand')->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertNotNull($details['ancestral_item_skill_data']);
        $this->assertSame($itemSkill->name, $details['ancestral_item_skill_data'][0]['name']);
    }

    public function test_for_stat_returns_empty_ancestral_item_skill_data_without_an_artifact(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertSame([], $details['ancestral_item_skill_data']);
    }

    public function test_for_stat_returns_empty_ancestral_item_skill_data_when_artifact_skill_does_not_affect_the_stat(): void
    {
        $itemSkill = $this->createItemSkill(['str_mod' => 2]);
        $artifact = $this->createItem(['type' => 'artifact']);
        $this->createItemSkillProgression([
            'item_id' => $artifact->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 0,
            'is_training' => false,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($artifact->refresh(), true, 'left_hand')->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->forStat('dur');

        $this->assertSame([], $details['ancestral_item_skill_data']);
    }

    public function test_for_stat_includes_class_specialty_details_for_damage_stat(): void
    {
        $character = $this->character->getCharacter();
        $damageStat = $character->damage_stat;

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'base_damage_stat_increase' => 4,
        ]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'equipped' => true,
        ]);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->forStat($damageStat);

        $this->assertNotNull($details['class_specialties']);
        $this->assertSame(4.0, $details['class_specialties'][0]['amount']);
    }

    public function test_get_map_reduction_details_returns_reduction_for_hell_map(): void
    {
        $hellMap = $this->createGameMap([
            'name' => MapName::HELL->value,
            'character_attack_reduction' => 0.5,
        ]);
        $character = $this->character->getCharacter();
        $character->map->update(['game_map_id' => $hellMap->id]);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->forStat('str');

        $this->assertSame($hellMap->name, $details['map_reduction']['map_name']);
        $this->assertSame(0.5, $details['map_reduction']['reduction_amount']);
    }

    public function test_get_map_reduction_details_returns_null_for_a_regular_map(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->forStat('str');

        $this->assertNull($details['map_reduction']);
    }

    public function test_get_map_reduction_details_returns_reduction_on_ice_plane_with_purgatory_item(): void
    {
        $iceMap = $this->createGameMap([
            'name' => MapName::ICE_PLANE->value,
            'character_attack_reduction' => 0.3,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => 'purgatory']))
            ->getCharacter();

        $character->map->update(['game_map_id' => $iceMap->id]);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->forStat('str');

        $this->assertSame($iceMap->name, $details['map_reduction']['map_name']);
        $this->assertSame(0.3, $details['map_reduction']['reduction_amount']);
    }

    public function test_get_map_reduction_details_includes_the_rolled_map_gem_character_power_reduction(): void
    {
        $character = $this->character->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['character_power_reduction' => 0.2]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->forStat('str');

        $this->assertSame($gameMap->name, $details['map_reduction']['map_name']);
        $this->assertSame(0.2, $details['map_reduction']['reduction_amount']);
    }

    public function test_build_specific_break_down_returns_health_break_down(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('health');

        $this->assertArrayHasKey('stat_amount', $details);
        $this->assertArrayHasKey('class_specialties', $details);
        $this->assertArrayHasKey('items_equipped', $details);
        $this->assertArrayHasKey('map_reduction', $details);
    }

    public function test_build_specific_break_down_for_health_includes_damage_stat_and_health_specialty_details(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'base_damage_stat_increase' => 4,
            'health_mod' => 25,
        ]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'equipped' => true,
        ]);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->buildSpecificBreakDown('health');

        $this->assertCount(2, $details['class_specialties']);
    }

    public function test_build_specific_break_down_returns_defence_break_down(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('ac');

        $this->assertArrayHasKey('class_bonus_details', $details);
        $this->assertArrayHasKey('boon_details', $details);
    }

    public function test_build_specific_break_down_for_defence_includes_class_bonus_details(): void
    {
        $character = $this->character->getCharacter();

        $skill = $this->createGameSkill([
            'game_class_id' => $character->game_class_id,
            'base_ac_mod_bonus_per_level' => 0.5,
        ]);

        $this->character->assignSkill($skill, 2);
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('ac');

        $this->assertNotNull($details['class_bonus_details']);
        $this->assertSame($skill->name, $details['class_bonus_details']['name']);
    }

    public function test_build_specific_break_down_for_defence_includes_class_specialty_details(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'base_ac_mod' => 3,
        ]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'equipped' => true,
        ]);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->buildSpecificBreakDown('ac');

        $this->assertNotNull($details['class_specialties']);
        $this->assertSame(3.0, $details['class_specialties'][0]['amount']);
    }

    public function test_build_specific_break_down_returns_weapon_damage_break_down(): void
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('weapon_damage');

        $this->assertSame($character->damage_stat, $details['damage_stat_name']);
        $this->assertArrayHasKey('non_equipped_damage_amount', $details);
        $this->assertIsNotString($details['total_damage_for_type']);
        $this->assertIsNotString($details['non_equipped_damage_amount']);
    }

    public function test_build_specific_break_down_returns_spell_damage_break_down(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('spell_damage');

        $this->assertArrayHasKey('damage_stat_name', $details);
    }

    public function test_build_specific_break_down_returns_ring_damage_break_down_without_class_bonuses(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('ring_damage');

        $this->assertNull($details['class_bonus_details']);
        $this->assertNull($details['boon_details']);
    }

    public function test_build_specific_break_down_returns_heal_for_break_down(): void
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('heal_for');

        $this->assertArrayHasKey('damage_stat_name', $details);
    }

    public function test_build_specific_break_down_returns_empty_array_for_unknown_type(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown('unknown-type');

        $this->assertSame([], $details);
    }

    public function test_build_damage_break_down_uses_alcoholic_non_equipped_percentage(): void
    {
        $character = $this->character->getCharacter();
        $character->class->update(['name' => 'Alcoholic']);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->buildSpecificBreakDown('weapon_damage');

        $this->assertSame(0.25, $details['non_equipped_percentage_of_stat_used']);
    }

    public function test_build_damage_break_down_uses_fighter_non_equipped_percentage(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character->refresh())->buildSpecificBreakDown('weapon_damage');

        $this->assertSame(0.05, $details['non_equipped_percentage_of_stat_used']);
    }

    public function test_build_damage_break_down_uses_default_non_equipped_percentage_for_other_classes(): void
    {
        $character = $this->character->getCharacter();
        $character->class->update(['name' => 'Thief']);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->buildSpecificBreakDown('weapon_damage');

        $this->assertSame(0.02, $details['non_equipped_percentage_of_stat_used']);
    }

    public function test_build_damage_break_down_returns_no_type_specific_attributes_for_an_unrecognized_item_type(): void
    {
        $character = $this->character->getCharacter();

        $details = $this->statModifierDetails->setCharacter($character)->buildDamageBreakDown(['body'], false);

        $this->assertArrayNotHasKey('masteries', $details);
    }

    public function test_build_damage_break_down_uses_heretic_spell_damage_percentage(): void
    {
        $character = $this->character->getCharacter();
        $character->class->update(['name' => 'Heretic']);

        $details = $this->statModifierDetails->setCharacter($character->refresh())->buildSpecificBreakDown('spell_damage');

        $this->assertSame(0.15, $details['percentage_of_stat_used']);
        $this->assertIsNotString($details['spell_damage_stat_amount_to_use']);
    }
}
