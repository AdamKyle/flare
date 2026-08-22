<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Setup;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Setup\TrinketryBatchCraftingSetupService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class TrinketryBatchCraftingSetupServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?TrinketryBatchCraftingSetupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(TrinketryBatchCraftingSetupService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_supports_only_trinketry(): void
    {
        $this->assertTrue($this->service->supports(BatchCraftingType::TRINKETRY));
        $this->assertFalse($this->service->supports(BatchCraftingType::ALCHEMY));
    }

    public function test_preview_is_always_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $preview = $this->service->preview($character, [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $this->assertNull($preview);
    }

    public function test_resolve_start_reports_a_blocker_without_a_meaningful_trinket(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_resolve_start_builds_experience_progress_when_a_meaningful_trinket_exists(): void
    {
        $trinketSkill = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($trinketSkill, 1, false)->givePlayerLocation()->getCharacter();

        $this->createItem(['type' => 'trinket', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 100]);

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $this->assertEmpty($result['blockers']);
        $this->assertSame('experience', $result['progress']['trinketry_mode']);
        $this->assertSame(0, $result['progress']['trinketry_xp_gained']);
    }
}
