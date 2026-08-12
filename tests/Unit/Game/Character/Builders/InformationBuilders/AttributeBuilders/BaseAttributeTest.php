<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\BaseAttribute;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class BaseAttributeTest extends TestCase
{
    use CreateClass, CreateGameSkill, RefreshDatabase;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?BaseAttribute $baseAttribute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
        $this->baseAttribute = resolve(BaseAttribute::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterStatBuilder = null;
        $this->baseAttribute = null;
    }

    public function test_fetch_base_attribute_from_skills_sums_bonus_per_level_across_skills(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $factory->getCharacter();

        $skill = $this->createGameSkill([
            'base_damage_mod_bonus_per_level' => 0.02,
        ]);
        $factory->assignSkill($skill, 5);
        $character = $factory->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->baseAttribute->initialize($character, $character->skills, $equipped);

        $result = $this->baseAttribute->fetchBaseAttributeFromSkills('base_damage');

        $this->assertSame(0.1, round($result, 2));
    }

    public function test_fetch_base_attribute_from_skills_details_includes_skill_matching_character_class(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $factory->getCharacter();

        $skill = $this->createGameSkill([
            'game_class_id' => $character->game_class_id,
            'base_damage_mod_bonus_per_level' => 0.05,
        ]);
        $factory->assignSkill($skill, 2);
        $character = $factory->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->baseAttribute->initialize($character, $character->skills, $equipped);

        $result = $this->baseAttribute->fetchBaseAttributeFromSkillsDetails('base_damage');

        $this->assertCount(1, $result);
        $this->assertSame($skill->name, $result[0]['name']);
        $this->assertSame(0.1, $result[0]['amount']);
    }

    public function test_fetch_base_attribute_from_skills_details_excludes_skill_for_a_different_class(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $factory->getCharacter();

        $otherClass = $this->createClass();

        $skill = $this->createGameSkill([
            'game_class_id' => $otherClass->id,
            'base_damage_mod_bonus_per_level' => 0.05,
        ]);
        $factory->assignSkill($skill, 2);
        $character = $factory->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->baseAttribute->initialize($character, $character->skills, $equipped);

        $result = $this->baseAttribute->fetchBaseAttributeFromSkillsDetails('base_damage');

        $this->assertSame([], $result);
    }
}
