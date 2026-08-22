<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Services\Capabilities\AlchemyBatchCraftingCapabilityService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class AlchemyBatchCraftingCapabilityServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?AlchemyBatchCraftingCapabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(AlchemyBatchCraftingCapabilityService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_build_reports_alchemy_available_but_no_experience_target_by_default(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character);

        $this->assertTrue($result['can_alchemy']);
        $this->assertFalse($result['can_alchemy_for_experience']);
        $this->assertNotNull($result['alchemy_skill']);
        $this->assertSame('Alchemy', $result['alchemy_skill']['skill_name']);
    }

    public function test_build_reports_can_alchemy_for_experience_when_a_meaningful_item_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createItem([
            'gold_dust_cost' => 10,
            'shards_cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $result = $this->service->build($character);

        $this->assertTrue($result['can_alchemy_for_experience']);
    }

    public function test_build_reports_alchemy_unavailable_when_no_alchemy_skill_type_is_configured(): void
    {
        GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->delete();

        $character = (new CharacterFactory)->createBaseCharacter(assignBaseSkill: false)->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character);

        $this->assertFalse($result['can_alchemy']);
        $this->assertNull($result['alchemy_skill']);
    }
}
