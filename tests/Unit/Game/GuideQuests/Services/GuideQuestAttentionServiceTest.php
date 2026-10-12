<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Game\GuideQuests\Services\GuideQuestAttentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateGuideQuestWalkthrough;

class GuideQuestAttentionServiceTest extends TestCase
{
    use CreateGuideQuest, CreateGuideQuestWalkthrough, RefreshDatabase;

    public function test_ready_state_creates_an_unacknowledged_attention_timestamp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $quest = $this->createGuideQuest();
        $service = new GuideQuestAttentionService;

        $service->recordReadyState($character, $quest, true);

        $this->assertTrue($service->hasUnacknowledgedAttention($character, [$quest]));
    }

    public function test_non_ready_state_does_not_create_attention(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $quest = $this->createGuideQuest();
        $service = new GuideQuestAttentionService;

        $service->recordReadyState($character, $quest, false);

        $this->assertFalse($service->hasUnacknowledgedAttention($character, [$quest]));
    }

    public function test_acknowledging_ready_attention_removes_the_unacknowledged_state(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $quest = $this->createGuideQuest();
        $this->createCharacterGuideQuestProgress([
            'character_id' => $character->id,
            'guide_quest_id' => $quest->id,
            'hand_in_ready_at' => now(),
        ]);
        $service = new GuideQuestAttentionService;

        $service->acknowledge($character, [$quest]);

        $this->assertFalse($service->hasUnacknowledgedAttention($character, [$quest]));
    }
}
