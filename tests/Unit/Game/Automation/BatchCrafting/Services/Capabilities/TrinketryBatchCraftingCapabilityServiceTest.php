<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Game\Automation\BatchCrafting\Services\Capabilities\TrinketryBatchCraftingCapabilityService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class TrinketryBatchCraftingCapabilityServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?TrinketryBatchCraftingCapabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(TrinketryBatchCraftingCapabilityService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_build_reports_false_when_character_has_no_trinketry_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character);

        $this->assertFalse($result['can_trinketry']);
        $this->assertNull($result['trinketry_skill']);
    }

    public function test_build_reports_true_when_skill_exists_and_a_meaningful_trinket_is_craftable(): void
    {
        $trinketSkill = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($trinketSkill, 1, false)->givePlayerLocation()->getCharacter();

        $this->createItem(['type' => 'trinket', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 100]);

        $result = $this->service->build($character);

        $this->assertTrue($result['can_trinketry']);
        $this->assertSame('Trinketry', $result['trinketry_skill']['skill_name']);
    }

    public function test_build_reports_false_when_skill_is_maxed(): void
    {
        $trinketSkill = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($trinketSkill, 5, false)->givePlayerLocation()->getCharacter();

        $this->createItem(['type' => 'trinket', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 100]);

        $result = $this->service->build($character);

        $this->assertFalse($result['can_trinketry']);
        $this->assertTrue($result['trinketry_skill']['is_maxed']);
    }
}
