<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\AlchemyExperienceStatusSection;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class AlchemyExperienceStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?AlchemyExperienceStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(AlchemyExperienceStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_alchemy_experience(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::ALCHEMY, 'experience'));
        $this->assertFalse($this->section->supports(BatchCraftingType::ALCHEMY, 'amount'));
        $this->assertFalse($this->section->supports(BatchCraftingType::TRINKETRY, 'experience'));
    }

    public function test_build_reports_factual_progress_and_actions_per_minute(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'name' => 'Status Tonic']);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'alchemy_mode' => 'experience',
                'alchemy_xp_gained' => 250,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($item->id, $result['current_item_id']);
        $this->assertSame('Status Tonic', $result['current_item_name']);
        $this->assertSame(AlchemyBatchMode::EXPERIENCE->executionWindowSize(), $result['alchemy_experience_progress']['actions_per_minute']);
        $this->assertSame(250, $result['alchemy_experience_progress']['alchemy_xp_gained']);
        $this->assertNull($result['set_progress']);
        $this->assertNull($result['experience_progress']);
        $this->assertNull($result['event_progress']);
    }

    public function test_build_reports_the_characters_alchemy_skill_progress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'alchemy_mode' => 'experience',
                'alchemy_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame('Alchemy', $result['alchemy_experience_progress']['alchemy_skill']['skill_name']);
        $this->assertArrayHasKey('level', $result['alchemy_experience_progress']['alchemy_skill']);
        $this->assertArrayHasKey('is_maxed', $result['alchemy_experience_progress']['alchemy_skill']);
    }

    public function test_build_reports_null_alchemy_skill_facts_when_the_character_has_no_alchemy_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills()->where('game_skill_id', GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first()->id)->delete();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'alchemy_mode' => 'experience',
                'alchemy_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertNull($result['alchemy_experience_progress']['alchemy_skill']);
    }
}
