<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class SkillTest extends TestCase
{
    use CreateGameSkill;
    use CreateItem;
    use RefreshDatabase;

    private CharacterFactory $characterFactory;

    private Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory())->createBaseCharacter();

        $this->character = $this->characterFactory->getCharacter()->refresh();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_new_factory_can_create_skill_instance(): void
    {
        $skill = Skill::factory()->make();

        $this->assertInstanceOf(Skill::class, $skill);
    }

    public function test_accessors_and_type_use_base_skill_fields(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $this->assertSame($baseSkill->name, $skill->getNameAttribute());
        $this->assertSame($baseSkill->description, $skill->getDescriptionAttribute());
        $this->assertSame($baseSkill->max_level, $skill->getMaxLevelAttribute());
        $this->assertSame($baseSkill->can_train, $skill->getCanTrainAttribute());
        $this->assertSame($baseSkill->skillType(), $skill->type());
        $this->assertEquals(SkillTypeValue::TRAINING, $skill->type());

        $baseSkill->update([
            'class_bonus' => null,
            'game_class_id' => null,
        ]);

        $skill->update(['level' => 3]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(0, $skill->getClassBonusAttribute());
        $this->assertNull($skill->getClassIdAttribute());

        $baseSkill->update([
            'class_bonus' => 2.0,
            'game_class_id' => $this->character->game_class_id,
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(6.0, $skill->getClassBonusAttribute());
        $this->assertSame($this->character->game_class_id, $skill->getClassIdAttribute());
    }

    public function test_reduces_time_and_movement_time_branches(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => null,
            'move_time_out_mod_bonus_per_level' => null,
            'unit_time_reduction' => 2.0,
            'building_time_reduction' => 3.0,
            'unit_movement_time_reduction' => 4.0,
        ]);

        $skill->update(['level' => 2]);

        $skill = $this->reloadSkill($skill);

        $this->assertFalse($skill->getReducesTimeAttribute());
        $this->assertFalse($skill->getReducesMovementTimeAttribute());
        $this->assertSame(4.0, $skill->getUnitTimeReductionAttribute());
        $this->assertSame(6.0, $skill->getBuildingTimeReductionAttribute());
        $this->assertSame(8.0, $skill->getUnitMovementTimeReductionAttribute());

        $baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 0.0,
            'move_time_out_mod_bonus_per_level' => -0.1,
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertFalse($skill->getReducesTimeAttribute());
        $this->assertFalse($skill->getReducesMovementTimeAttribute());

        $baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 0.1,
            'move_time_out_mod_bonus_per_level' => 0.1,
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertTrue($skill->getReducesTimeAttribute());
        $this->assertTrue($skill->getReducesMovementTimeAttribute());
    }

    public function test_base_damage_healing_and_ac_mods_return_zero_when_per_level_invalid(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'base_damage_mod_bonus_per_level' => null,
            'base_healing_mod_bonus_per_level' => 0.0,
            'base_ac_mod_bonus_per_level' => -0.1,
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(0.0, $skill->getBaseDamageModAttribute());
        $this->assertSame(0.0, $skill->getBaseHealingModAttribute());
        $this->assertSame(0.0, $skill->getBaseACModAttribute());
    }

    public function test_base_damage_healing_and_ac_mods_return_computed_when_per_level_positive(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'base_damage_mod_bonus_per_level' => 0.1,
            'base_healing_mod_bonus_per_level' => 0.1,
            'base_ac_mod_bonus_per_level' => 0.1,
        ]);

        $skill->update(['level' => 2]);

        $skill = $this->reloadSkill($skill);

        $this->assertEqualsWithDelta(0.20, $skill->getBaseDamageModAttribute(), 0.00001);
        $this->assertEqualsWithDelta(0.20, $skill->getBaseHealingModAttribute(), 0.00001);
        $this->assertEqualsWithDelta(0.20, $skill->getBaseACModAttribute(), 0.00001);
    }

    public function test_fight_time_out_mod_caps_at_half(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update(['fight_time_out_mod_bonus_per_level' => null]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(0.0, $skill->getFightTimeOutModAttribute());

        $baseSkill->update(['fight_time_out_mod_bonus_per_level' => 0.4]);

        $skill->update(['level' => 2]);

        $equippedItem = $this->createItem([
            'skill_name' => $baseSkill->name,
            'fight_time_out_mod_bonus' => 0.15,
        ]);

        $this->character->refresh()->load('inventory');

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(0.50, $skill->getFightTimeOutModAttribute());
    }

    public function test_move_time_out_mod_caps_at_one_and_returns_computed_when_below_cap(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update(['move_time_out_mod_bonus_per_level' => null]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(0.0, $skill->getMoveTimeOutModAttribute());

        $baseSkill->update(['move_time_out_mod_bonus_per_level' => 0.4]);

        $skill->update(['level' => 2]);

        $equippedItem = $this->createItem([
            'skill_name' => $baseSkill->name,
            'move_time_out_mod_bonus' => 0.2,
        ]);

        $this->character->refresh()->load('inventory');

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(1.0, $skill->getMoveTimeOutModAttribute());

        $this->character->inventory->slots()->delete();

        $baseSkill->update(['move_time_out_mod_bonus_per_level' => 0.2]);

        $skill->update(['level' => 1]);

        $equippedItem = $this->createItem([
            'skill_name' => $baseSkill->name,
            'move_time_out_mod_bonus' => 0.1,
        ]);

        $this->character->refresh()->load('inventory');

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertEqualsWithDelta(0.5, $skill->getMoveTimeOutModAttribute(), 0.00001);
    }

    public function test_skill_bonus_returns_zero_when_per_level_null_and_returns_one_at_max_level(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Alchemy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'skill_bonus_per_level' => null,
            'max_level' => 10,
        ]);

        $skill->update(['level' => 3]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(0.0, $skill->getSkillBonusAttribute());

        $baseSkill->update([
            'skill_bonus_per_level' => 0.1,
            'max_level' => 3,
        ]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(1.0, $skill->getSkillBonusAttribute());
    }

    public function test_skill_bonus_clamps_at_one_and_applies_accuracy_race_and_class_mods(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'skill_bonus_per_level' => 0.4,
            'max_level' => 10,
        ]);

        $skill->update(['level' => 4]);

        $this->character->refresh()->load(['race', 'class']);

        $this->character->race->update(['accuracy_mod' => 0.10]);
        $this->character->class->update(['accuracy_mod' => 0.10]);

        $skill = $this->reloadSkill($skill);

        $this->assertSame(1.0, $skill->getSkillBonusAttribute());
    }

    public function test_skill_bonus_applies_class_specific_training_bonus_for_blacksmith_weapon_crafting(): void
    {
        $blacksmithFactory = (new CharacterFactory())->createBaseCharacter([], ['name' => 'Blacksmith']);
        $blacksmith = $blacksmithFactory->getCharacter()->refresh();

        $weaponCrafting = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::TRAINING->value,
            'can_train' => true,
            'skill_bonus_per_level' => 0.1,
            'max_level' => 10,
        ]);

        $blacksmithFactory->assignSkill($weaponCrafting, 2);

        $skill = Skill::query()
            ->where('character_id', $blacksmith->id)
            ->where('game_skill_id', $weaponCrafting->id)
            ->with(['baseSkill', 'character'])
            ->firstOrFail();

        $this->assertEqualsWithDelta(0.25, $skill->getSkillBonusAttribute(), 0.00001);
    }

    public function test_skill_training_bonus_sums_equipped_quest_boons_and_class_specific_and_skips_null_cases(): void
    {
        $alchemistFactory = (new CharacterFactory())->createBaseCharacter([], ['name' => 'Arcane Alchemist']);
        $alchemist = $alchemistFactory->getCharacter()->refresh();

        $skill = $this->getCharacterSkillByName($alchemist, 'Alchemy');

        $skill->update(['level' => 2]);

        $skill = $this->reloadSkill($skill);

        $alchemist->refresh()->load('inventory');

        $equippedItem = $this->createItem([
            'skill_name' => $skill->baseSkill->name,
            'skill_training_bonus' => 0.2,
        ]);

        $alchemist->inventory->slots()->create([
            'inventory_id' => $alchemist->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'skill_name' => $skill->baseSkill->name,
            'skill_training_bonus' => 0.1,
        ]);

        $alchemist->inventory->slots()->create([
            'inventory_id' => $alchemist->inventory->id,
            'item_id' => $questItem->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $boonValueItem = $this->createItem(['increase_skill_training_bonus_by' => 0.05]);

        $alchemist->boons()->create([
            'character_id' => $alchemist->id,
            'item_id' => $boonValueItem->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $boonNullValueItem = $this->createItem(['increase_skill_training_bonus_by' => null]);

        $alchemist->boons()->create([
            'character_id' => $alchemist->id,
            'item_id' => $boonNullValueItem->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $boonItemUsedWillBeNullItem = $this->createItem(['increase_skill_training_bonus_by' => 0.99]);

        $alchemist->boons()->create([
            'character_id' => $alchemist->id,
            'item_id' => $boonItemUsedWillBeNullItem->id,
            'last_for_minutes' => 10,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(10),
        ]);

        $skill = Skill::query()
            ->whereKey($skill->id)
            ->with(['baseSkill', 'character.boons.itemUsed'])
            ->firstOrFail();

        $boonToNullOut = $skill->character->boons->first(function ($boon) use ($boonItemUsedWillBeNullItem) {
            return $boon->item_id === $boonItemUsedWillBeNullItem->id;
        });

        $boonToNullOut->setRelation('itemUsed', null);

        $this->assertEqualsWithDelta(0.5, $skill->getSkillTrainingBonusAttribute(), 0.00001);
    }

    public function test_item_skill_breakdown_includes_only_positive_bonuses_from_equipped_and_quest_matching_skill(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Alchemy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'skill_bonus_per_level' => 0.0,
            'max_level' => 10,
        ]);

        $skill = $this->reloadSkill($skill);

        $this->character->refresh()->load('inventory');

        $positiveEquipped = $this->createItem([
            'skill_name' => $baseSkill->name,
            'skill_bonus' => 0.2,
        ]);

        $zeroEquipped = $this->createItem([
            'skill_name' => $baseSkill->name,
            'skill_bonus' => 0.0,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $positiveEquipped->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $zeroEquipped->id,
            'equipped' => true,
            'position' => 'left-hand',
        ]);

        $positiveQuest = $this->createItem([
            'type' => 'quest',
            'skill_name' => $baseSkill->name,
            'skill_bonus' => 0.3,
        ]);

        $questWrongType = $this->createItem([
            'type' => 'weapon',
            'skill_name' => $baseSkill->name,
            'skill_bonus' => 0.3,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $positiveQuest->id,
            'equipped' => false,
            'position' => 'slot-1',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $questWrongType->id,
            'equipped' => false,
            'position' => 'slot-2',
        ]);

        $skill = $this->reloadSkill($skill);

        $breakdown = $skill->getItemSkillBreakdown();

        $this->assertCount(2, $breakdown);

        $first = $breakdown[0];
        $second = $breakdown[1];

        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('position', $first);
        $this->assertArrayHasKey('affix_count', $first);
        $this->assertArrayHasKey('is_unique', $first);
        $this->assertArrayHasKey('is_mythic', $first);
        $this->assertArrayHasKey('is_cosmic', $first);
        $this->assertArrayHasKey('holy_stacks_applied', $first);
        $this->assertArrayHasKey('skill_bonus', $first);

        $this->assertTrue(is_bool($first['is_cosmic']) || is_null($first['is_cosmic']));

        $this->assertArrayHasKey('skill_bonus', $second);

        $this->assertEqualsWithDelta(0.2, $first['skill_bonus'], 0.00001);
        $this->assertEqualsWithDelta(0.3, $second['skill_bonus'], 0.00001);

        $trainingBreakdown = $skill->getItemSkillBreakdown('skill_training_bonus');

        $this->assertIsArray($trainingBreakdown);
    }

    public function test_fight_time_out_mod_returns_computed_when_below_cap(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Accuracy');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update(['fight_time_out_mod_bonus_per_level' => 0.1]);

        $skill->update(['level' => 1]);

        $skill = $this->reloadSkill($skill);

        $baseline = $skill->getFightTimeOutModAttribute();

        $equippedItem = $this->createItem([
            'skill_name' => $baseSkill->name,
            'fight_time_out_mod_bonus' => 0.1,
        ]);

        $this->character->refresh()->load('inventory');

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $equippedItem->id,
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $skill = $this->reloadSkill($skill);

        $withItem = $skill->getFightTimeOutModAttribute();

        $this->assertLessThan(0.50, $baseline);
        $this->assertLessThan(0.50, $withItem);
        $this->assertEqualsWithDelta($baseline + 0.1, $withItem, 0.00001);
    }

    public function test_skill_bonus_applies_looting_race_and_class_mods(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Looting');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'skill_bonus_per_level' => 0.1,
            'max_level' => 10,
        ]);

        $skill->update(['level' => 2]);

        $this->character->refresh()->load(['race', 'class']);

        $this->character->race->update(['looting_mod' => 0.15]);
        $this->character->class->update(['looting_mod' => 0.10]);

        $skill = $this->reloadSkill($skill);

        $this->assertEqualsWithDelta(0.35, $skill->getSkillBonusAttribute(), 0.00001);
    }

    public function test_skill_bonus_applies_dodge_race_and_class_mods(): void
    {
        $skill = $this->getCharacterSkillByName($this->character, 'Dodge');

        $baseSkill = $skill->baseSkill;

        $baseSkill->update([
            'skill_bonus_per_level' => 0.1,
            'max_level' => 10,
        ]);

        $skill->update(['level' => 2]);

        $this->character->refresh()->load(['race', 'class']);

        $this->character->race->update(['dodge_mod' => 0.20]);
        $this->character->class->update(['dodge_mod' => 0.10]);

        $skill = $this->reloadSkill($skill);

        $this->assertEqualsWithDelta(0.40, $skill->getSkillBonusAttribute(), 0.00001);
    }

    private function getCharacterSkillByName(Character $character, string $skillName): Skill
    {
        return Skill::query()
            ->where('character_id', $character->id)
            ->whereHas('baseSkill', function ($query) use ($skillName) {
                $query->where('name', $skillName);
            })
            ->with(['baseSkill', 'character'])
            ->firstOrFail();
    }

    private function reloadSkill(Skill $skill): Skill
    {
        return Skill::query()
            ->whereKey($skill->id)
            ->with(['baseSkill', 'character'])
            ->firstOrFail();
    }
}
