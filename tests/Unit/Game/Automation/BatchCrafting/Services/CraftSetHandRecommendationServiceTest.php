<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Services\CraftSetHandRecommendationService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftSetHandRecommendationServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CraftSetHandRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(CraftSetHandRecommendationService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_recommend_returns_the_highest_craftable_item_for_a_weapon_hand_type(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();

        $weakerSword = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);
        $strongerSword = $this->createItem(['cost' => 10, 'skill_level_required' => 4, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);

        $result = $this->service->recommend($character, 'sword');

        $this->assertSame($strongerSword->id, $result?->id);
        $this->assertNotSame($weakerSword->id, $result?->id);
    }

    public function test_recommend_returns_the_highest_craftable_shield_for_the_shield_hand_type(): void
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($armourCrafting, 5, false)
            ->getCharacter();

        $weakerShield = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'shield', 'can_craft' => true, 'default_position' => 'shield']);
        $strongerShield = $this->createItem(['cost' => 10, 'skill_level_required' => 4, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'shield', 'can_craft' => true, 'default_position' => 'shield']);

        $result = $this->service->recommend($character, 'shield');

        $this->assertSame($strongerShield->id, $result?->id);
        $this->assertNotSame($weakerShield->id, $result?->id);
    }

    public function test_recommend_returns_null_for_an_invalid_hand_type_without_throwing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->recommend($character, 'not-a-real-hand-type');

        $this->assertNull($result);
    }

    public function test_recommend_returns_null_when_the_character_has_no_applicable_crafting_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);

        $result = $this->service->recommend($character, 'sword');

        $this->assertNull($result);
    }
}
