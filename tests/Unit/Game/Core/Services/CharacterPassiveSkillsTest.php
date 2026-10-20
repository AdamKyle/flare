<?php

namespace Tests\Unit\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Game\Core\Services\CharacterPassiveSkills;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterPassiveSkillsTest extends TestCase
{
    use RefreshDatabase;

    public function testPassiveQuestDetailsUseLowestQuestIdAndPreserveNestedMissingAndCompletedStates(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Inspected Character']);
        $parentPassive = PassiveSkill::factory()->create(['name' => 'Parent Passive', 'is_parent' => true]);
        $firstChildPassive = PassiveSkill::factory()->create([
            'name' => 'First Child',
            'is_parent' => false,
            'parent_skill_id' => $parentPassive->id,
        ]);
        $secondChildPassive = PassiveSkill::factory()->create([
            'name' => 'Second Child',
            'is_parent' => false,
            'parent_skill_id' => $parentPassive->id,
        ]);
        $parentCharacterPassive = CharacterPassiveSkill::factory()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $parentPassive->id,
        ]);
        $firstChild = CharacterPassiveSkill::factory()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $firstChildPassive->id,
            'parent_skill_id' => $parentCharacterPassive->id,
        ]);
        $secondChild = CharacterPassiveSkill::factory()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $secondChildPassive->id,
            'parent_skill_id' => $parentCharacterPassive->id,
        ]);
        $npc = Npc::factory()->create();
        $lowestQuest = Quest::factory()->create([
            'name' => 'Lowest Quest',
            'npc_id' => $npc->id,
            'unlocks_passive_id' => $parentPassive->id,
        ]);
        Quest::factory()->create([
            'name' => 'Later Quest',
            'npc_id' => $npc->id,
            'unlocks_passive_id' => $parentPassive->id,
        ]);
        QuestsCompleted::factory()->create([
            'character_id' => $character->id,
            'quest_id' => $lowestQuest->id,
        ]);

        $result = resolve(CharacterPassiveSkills::class)->getPassiveSkills($character);
        $parent = $result->firstWhere('passive_skill_id', $parentPassive->id);

        $this->assertSame('Lowest Quest', $parent->quest_name);
        $this->assertTrue($parent->is_quest_complete);
        $this->assertSame([$firstChild->id, $secondChild->id], $parent->children->pluck('id')->all());
        $this->assertNull($parent->children[0]->quest_name);
        $this->assertFalse($parent->children[0]->is_quest_complete);
        $this->assertNull($parent->children[1]->quest_name);
        $this->assertFalse($parent->children[1]->is_quest_complete);
    }

    public function testPassiveQuestCompletionRemainsCharacterSpecific(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $otherUser = User::factory()->create();
        $otherCharacter = Character::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Character']);
        $passive = PassiveSkill::factory()->create(['name' => 'Character Specific Passive', 'is_parent' => true]);
        CharacterPassiveSkill::factory()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $passive->id,
        ]);
        $npc = Npc::factory()->create();
        $quest = Quest::factory()->create([
            'name' => 'Character Specific Quest',
            'npc_id' => $npc->id,
            'unlocks_passive_id' => $passive->id,
        ]);
        QuestsCompleted::factory()->create([
            'character_id' => $otherCharacter->id,
            'quest_id' => $quest->id,
        ]);

        $result = resolve(CharacterPassiveSkills::class)->getPassiveSkills($character);
        $resultPassive = $result->firstWhere('passive_skill_id', $passive->id);

        $this->assertSame('Character Specific Quest', $resultPassive->quest_name);
        $this->assertFalse($resultPassive->is_quest_complete);
    }
}
