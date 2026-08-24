<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\Skill;
use App\Game\Character\CharacterCreation\Services\CharacterBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterCreation\CharacterBuilderServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterClassRank;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateQuest;

class CharacterBuilderServiceTest extends TestCase
{
    use CreateCharacterClassRank,
        CreateGameSkill,
        CreateNpc,
        CreatePassiveSkill,
        CreateQuest,
        RefreshDatabase;

    private ?CharacterBuilderService $builder = null;

    private ?Character $character = null;

    private CharacterBuilderServiceFactory $characterBuilderServiceFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new CharacterBuilderService;
        $this->characterBuilderServiceFactory = new CharacterBuilderServiceFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->builder = null;
        $this->character = null;
        unset($this->characterBuilderServiceFactory);
    }

    public function test_assign_skills_creates_missing_global_and_class_skills(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter();

        $globalSkill = $this->createGameSkill(['game_class_id' => null]);
        $classSkill = $this->createGameSkill(['game_class_id' => $this->character->game_class_id]);

        $this->builder->setCharacter($this->character)->assignSkills();

        $this->assertNotNull(
            Skill::where('character_id', $this->character->id)->where('game_skill_id', $globalSkill->id)->first()
        );
        $this->assertNotNull(
            Skill::where('character_id', $this->character->id)->where('game_skill_id', $classSkill->id)->first()
        );
    }

    public function test_assign_skills_does_not_duplicate_an_already_assigned_skill(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter();

        $globalSkill = $this->createGameSkill(['game_class_id' => null]);

        $this->builder->setCharacter($this->character)->assignSkills();
        $this->builder->setCharacter($this->character->refresh())->assignSkills();

        $this->assertSame(
            1,
            Skill::where('character_id', $this->character->id)->where('game_skill_id', $globalSkill->id)->count()
        );
    }

    public function test_assign_passive_skills_creates_top_level_and_locked_child(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter();

        $parent = $this->createPassiveSkill([
            'is_locked' => false,
            'unlocks_at_level' => 0,
            'parent_skill_id' => null,
        ]);

        $child = $this->createPassiveSkill([
            'parent_skill_id' => $parent->id,
            'unlocks_at_level' => 1,
        ]);

        $this->builder->setCharacter($this->character)->assignPassiveSkills();

        $parentRow = CharacterPassiveSkill::where('character_id', $this->character->id)->where('passive_skill_id', $parent->id)->first();
        $childRow = CharacterPassiveSkill::where('character_id', $this->character->id)->where('passive_skill_id', $child->id)->first();

        $this->assertFalse($parentRow->is_locked);
        $this->assertTrue($childRow->is_locked);
        $this->assertSame($parentRow->id, $childRow->parent_skill_id);
    }

    public function test_assign_passive_skills_updates_locked_status_for_an_existing_row(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter();

        $parent = $this->createPassiveSkill([
            'is_locked' => false,
            'unlocks_at_level' => 0,
            'parent_skill_id' => null,
        ]);

        $this->character->passiveSkills()->create([
            'character_id' => $this->character->id,
            'passive_skill_id' => $parent->id,
            'current_level' => 0,
            'hours_to_next' => $parent->hours_per_level,
            'is_locked' => true,
            'parent_skill_id' => null,
        ]);

        $this->builder->setCharacter($this->character->refresh())->assignPassiveSkills();

        $this->assertSame(
            1,
            CharacterPassiveSkill::where('character_id', $this->character->id)->where('passive_skill_id', $parent->id)->count()
        );
        $this->assertFalse(
            CharacterPassiveSkill::where('character_id', $this->character->id)->where('passive_skill_id', $parent->id)->first()->is_locked
        );
    }

    public function test_assign_passive_skills_locks_a_passive_behind_an_uncompleted_quest(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter();

        $passiveSkill = $this->createPassiveSkill([
            'is_locked' => false,
            'unlocks_at_level' => 0,
            'parent_skill_id' => null,
        ]);

        $this->createQuest(['unlocks_passive_id' => $passiveSkill->id, 'npc_id' => $this->createNpc()->id]);

        $this->builder->setCharacter($this->character)->assignPassiveSkills();

        $row = CharacterPassiveSkill::where('character_id', $this->character->id)->where('passive_skill_id', $passiveSkill->id)->first();

        $this->assertTrue($row->is_locked);
    }

    public function test_assign_passive_skills_unlocks_a_passive_behind_a_completed_quest(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter();

        $passiveSkill = $this->createPassiveSkill([
            'is_locked' => false,
            'unlocks_at_level' => 0,
            'parent_skill_id' => null,
        ]);

        $quest = $this->createQuest(['unlocks_passive_id' => $passiveSkill->id, 'npc_id' => $this->createNpc()->id]);

        $this->createCompletedQuest([
            'character_id' => $this->character->id,
            'quest_id' => $quest->id,
        ]);

        $this->builder->setCharacter($this->character->refresh())->assignPassiveSkills();

        $row = CharacterPassiveSkill::where('character_id', $this->character->id)->where('passive_skill_id', $passiveSkill->id)->first();

        $this->assertFalse($row->is_locked);
    }

    public function test_assign_weapon_masteries_uses_string_mapping_for_a_single_weapon_class(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter('Fighter');

        $classRank = $this->createCharacterClassRank([
            'character_id' => $this->character->id,
            'game_class_id' => $this->character->game_class_id,
        ]);

        $this->builder->assignWeaponMasteriesToClassRanks($classRank);

        $masteries = $classRank->weaponMasteries()->get()->keyBy('weapon_type');

        $this->assertSame(5, $masteries->get('sword')->level);
        $this->assertSame(0, $masteries->get('dagger')->level);
    }

    public function test_assign_weapon_masteries_grants_prisoner_full_level_only_for_the_first_mapped_type(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter('Prisoner');

        $classRank = $this->createCharacterClassRank([
            'character_id' => $this->character->id,
            'game_class_id' => $this->character->game_class_id,
        ]);

        $this->builder->assignWeaponMasteriesToClassRanks($classRank);

        $masteries = $classRank->weaponMasteries()->get()->keyBy('weapon_type');

        $this->assertSame(5, $masteries->get('weapon')->level);
        $this->assertSame(0, $masteries->get('stave')->level);
    }

    public function test_assign_weapon_masteries_grants_merchant_specific_levels_for_mapped_types(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter('Merchant');

        $classRank = $this->createCharacterClassRank([
            'character_id' => $this->character->id,
            'game_class_id' => $this->character->game_class_id,
        ]);

        $this->builder->assignWeaponMasteriesToClassRanks($classRank);

        $masteries = $classRank->weaponMasteries()->get()->keyBy('weapon_type');

        $this->assertSame(2, $masteries->get('bow')->level);
        $this->assertSame(3, $masteries->get('stave')->level);
        $this->assertSame(0, $masteries->get('sword')->level);
    }

    public function test_assign_weapon_masteries_grants_default_level_for_non_special_mapped_class(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter('Thief');

        $classRank = $this->createCharacterClassRank([
            'character_id' => $this->character->id,
            'game_class_id' => $this->character->game_class_id,
        ]);

        $this->builder->assignWeaponMasteriesToClassRanks($classRank);

        $masteries = $classRank->weaponMasteries()->get()->keyBy('weapon_type');

        $this->assertSame(5, $masteries->get('dagger')->level);
        $this->assertSame(5, $masteries->get('bow')->level);
        $this->assertSame(0, $masteries->get('sword')->level);
    }

    public function test_assign_weapon_masteries_grants_zero_level_for_an_unmapped_class(): void
    {
        $this->character = $this->characterBuilderServiceFactory->makeBareCharacter('Unmapped Custom Class');

        $classRank = $this->createCharacterClassRank([
            'character_id' => $this->character->id,
            'game_class_id' => $this->character->game_class_id,
        ]);

        $this->builder->assignWeaponMasteriesToClassRanks($classRank);

        $masteries = $classRank->weaponMasteries()->get()->keyBy('weapon_type');

        $this->assertSame(0, $masteries->get('sword')->level);
        $this->assertSame(0, $masteries->get('bow')->level);
    }
}
