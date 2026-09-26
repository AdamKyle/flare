<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Values\SkillBonusAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class SkillBonusServiceTest extends TestCase
{
    use CreateCharacterBoon, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?SkillBonusService $skillBonusService;

    private ?GameSkill $gameSkill;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skillBonusService = new SkillBonusService(new SkillBonusContextService);

        $this->gameSkill = $this->createGameSkill([
            'name' => 'Bonus Test Skill',
            'skill_bonus_per_level' => 0.01,
            'max_level' => 10,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter(assignPassiveSkills: false)->assignSkill($this->gameSkill, 5);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->skillBonusService = null;
        $this->gameSkill = null;
        $this->character = null;
    }

    public function test_skill_bonus_grows_with_the_skill_level(): void
    {
        $skill = $this->character->getCharacter()->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.04, $this->skillBonusService->skillBonus($skill), 0.00001);
    }

    public function test_skill_bonus_is_complete_at_max_level(): void
    {
        $skill = $this->character->getCharacter()->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $skill->update(['level' => 10]);

        $this->assertSame(1.0, $this->skillBonusService->skillBonus($skill->refresh()));
    }

    public function test_skill_bonus_is_capped_at_one_when_items_push_it_higher(): void
    {
        $questItem = $this->createItem(['type' => 'quest', 'skill_name' => $this->gameSkill->name, 'skill_bonus' => 0.99]);

        $skill = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter()
            ->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertSame(1.0, $this->skillBonusService->skillBonus($skill));
    }

    public function test_skill_bonus_includes_equipped_item_contribution(): void
    {
        $prefix = $this->createItemAffix(['skill_name' => $this->gameSkill->name, 'skill_bonus' => 0.10]);
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);

        $skill = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter()
            ->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.14, $this->skillBonusService->skillBonus($skill), 0.00001);
    }

    public function test_skill_bonus_includes_quest_item_contribution(): void
    {
        $questItem = $this->createItem(['type' => 'quest', 'skill_name' => $this->gameSkill->name, 'skill_bonus' => 0.20]);

        $skill = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter()
            ->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.24, $this->skillBonusService->skillBonus($skill), 0.00001);
    }

    public function test_skill_bonus_includes_equipped_set_items_when_no_inventory_item_is_equipped(): void
    {
        $prefix = $this->createItemAffix(['skill_name' => $this->gameSkill->name, 'skill_bonus' => 0.10]);
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);

        $skill = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0, 'left-hand', true)->getCharacter()
            ->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.14, $this->skillBonusService->skillBonus($skill), 0.00001);
    }

    public function test_skill_bonus_includes_stacked_active_boon_contribution(): void
    {
        $character = $this->character->getCharacter();
        $boonItem = $this->createItem(['type' => 'alchemy', 'increase_skill_bonus_by' => 0.05, 'can_stack' => true]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $boonItem->id,
            'amount_used' => 2,
            'last_for_minutes' => 60,
            'started' => now(),
            'complete' => now()->addHour(),
        ]);

        $skill = $character->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.14, $this->skillBonusService->skillBonus($skill), 0.00001);
    }

    public function test_expired_boon_does_not_contribute_to_skill_bonus(): void
    {
        $character = $this->character->getCharacter();
        $boonItem = $this->createItem(['type' => 'alchemy', 'increase_skill_bonus_by' => 0.05, 'can_stack' => true]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $boonItem->id,
            'amount_used' => 2,
            'last_for_minutes' => 60,
            'started' => now()->subHours(2),
            'complete' => now()->subHour(),
        ]);

        $skill = $character->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.04, $this->skillBonusService->skillBonus($skill), 0.00001);
    }

    public function test_skill_training_bonus_includes_equipped_and_quest_items(): void
    {
        $prefix = $this->createItemAffix(['skill_name' => $this->gameSkill->name, 'skill_training_bonus' => 0.10]);
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $questItem = $this->createItem(['type' => 'quest', 'skill_name' => $this->gameSkill->name, 'skill_training_bonus' => 0.20]);

        $skill = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->giveItem($questItem)->getCharacter()
            ->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.30, $this->skillBonusService->skillTrainingBonus($skill), 0.00001);
    }

    public function test_blacksmith_receives_class_training_bonus_for_weapon_crafting(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'skill_bonus_per_level' => 0.01, 'max_level' => 10]);

        $skill = (new CharacterFactory)->createBaseCharacter(classOptions: ['name' => 'Blacksmith'], assignPassiveSkills: false)
            ->assignSkill($weaponCrafting, 1)->getCharacter()
            ->skills->where('game_skill_id', $weaponCrafting->id)->first();

        $this->assertEqualsWithDelta(0.15, $this->skillBonusService->skillTrainingBonus($skill), 0.00001);
    }

    public function test_class_crafting_bonus_is_added_after_the_skill_bonus_cap(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'skill_bonus_per_level' => 0.01, 'max_level' => 10]);

        $skill = (new CharacterFactory)->createBaseCharacter(classOptions: ['name' => 'Blacksmith'], assignPassiveSkills: false)
            ->assignSkill($weaponCrafting, 10)->getCharacter()
            ->skills->where('game_skill_id', $weaponCrafting->id)->first();

        $this->assertEqualsWithDelta(1.15, $this->skillBonusService->skillBonus($skill), 0.00001);
    }

    public function test_base_damage_mod_ignores_quest_items(): void
    {
        $this->gameSkill->update(['base_damage_mod_bonus_per_level' => 0.01]);

        $questItem = $this->createItem(['type' => 'quest', 'skill_name' => $this->gameSkill->name, 'base_damage_mod' => 0.50]);

        $skill = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter()
            ->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertEqualsWithDelta(0.05, $this->skillBonusService->baseDamageMod($skill), 0.00001);
    }

    public function test_base_damage_mod_is_zero_when_the_skill_grants_no_damage_modifier(): void
    {
        $skill = $this->character->getCharacter()->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertSame(0.0, $this->skillBonusService->baseDamageMod($skill));
    }

    public function test_fight_time_out_mod_is_capped_at_half(): void
    {
        $this->gameSkill->update(['fight_time_out_mod_bonus_per_level' => 0.30]);

        $skill = $this->character->getCharacter()->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertSame(0.50, $this->skillBonusService->fightTimeOutMod($skill));
    }

    public function test_move_time_out_mod_is_capped_at_one(): void
    {
        $this->gameSkill->update(['move_time_out_mod_bonus_per_level' => 0.30]);

        $skill = $this->character->getCharacter()->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $this->assertSame(1.0, $this->skillBonusService->moveTimeOutMod($skill));
    }

    public function test_item_bonus_breakdown_lists_the_contributing_equipped_item(): void
    {
        $prefix = $this->createItemAffix(['skill_name' => $this->gameSkill->name, 'skill_bonus' => 0.10]);
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);

        $skill = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter()
            ->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $breakdown = $this->skillBonusService->itemBonusBreakdown($skill, SkillBonusAttribute::SKILL_BONUS);

        $this->assertCount(1, $breakdown);
        $this->assertSame($item->id, $breakdown[0]['item_id']);
        $this->assertEqualsWithDelta(0.10, $breakdown[0]['skill_bonus'], 0.00001);
    }

    public function test_loaded_relations_give_the_same_skill_bonus_as_database_reads(): void
    {
        $prefix = $this->createItemAffix(['skill_name' => $this->gameSkill->name, 'skill_bonus' => 0.10]);
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $questItem = $this->createItem(['type' => 'quest', 'skill_name' => $this->gameSkill->name, 'skill_bonus' => 0.20]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->giveItem($questItem)->getCharacter();

        $skillWithLoadedRelations = Skill::with('character.inventory.slots.item')
            ->where('character_id', $character->id)
            ->where('game_skill_id', $this->gameSkill->id)
            ->first();

        $skillWithoutRelations = Skill::where('character_id', $character->id)
            ->where('game_skill_id', $this->gameSkill->id)
            ->first();

        $this->assertEqualsWithDelta(
            $this->skillBonusService->skillBonus($skillWithoutRelations),
            $this->skillBonusService->skillBonus($skillWithLoadedRelations),
            0.00001,
        );
    }
}
