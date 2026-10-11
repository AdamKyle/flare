<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Events\UpdateCharacterSkills;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class SkillsControllerTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

    private CharacterFactory $characterFactory;

    private Character $character;

    private GameSkill $gameSkill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gameSkill = $this->createGameSkill([
            'name' => 'Accuracy',
            'type' => SkillTypeValue::TRAINING->value,
            'can_train' => true,
        ]);

        $this->characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($this->gameSkill);
        $this->character = $this->characterFactory->getCharacter();

        Event::fake([UpdateCharacterSkills::class]);
    }

    public function test_skill_information_returns_training_management_authority(): void
    {
        $this->characterFactory->assignAutomation([]);
        $character = $this->characterFactory->getCharacter();
        $skill = $character->skills()->where('game_skill_id', $this->gameSkill->id)->firstOrFail();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/skill/'.$character->id.'/'.$skill->id);

        $response->assertOk()
            ->assertJsonPath('can_manage_training', false)
            ->assertJsonPath(
                'training_restriction_message',
                'You cannot do that while Exploration automation is running. Cancel it first.'
            );
    }

    public function test_train_returns_and_broadcasts_the_updated_training_skills(): void
    {
        $skill = $this->character->skills()->where('game_skill_id', $this->gameSkill->id)->firstOrFail();

        $response = $this->actingAs($this->character->user)
            ->postJson('/api/skill/train/'.$this->character->id, [
                'skill_id' => $skill->id,
                'xp_percentage' => 0.10,
            ]);

        $response->assertOk()
            ->assertJsonPath('skills.training_skills.0.is_training', true)
            ->assertJsonPath('skills.training_skills.0.xp_towards', 0.10);
        Event::assertDispatched(UpdateCharacterSkills::class);
    }

    public function test_cancel_returns_and_broadcasts_the_updated_training_skills(): void
    {
        $skill = $this->character->skills()->where('game_skill_id', $this->gameSkill->id)->firstOrFail();
        $skill->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $response = $this->actingAs($this->character->user)
            ->postJson('/api/skill/cancel-train/'.$this->character->id.'/'.$skill->id);

        $response->assertOk()
            ->assertJsonPath('skills.training_skills.0.is_training', false);
        $this->assertEquals(
            0.0,
            $response->json('skills.training_skills.0.xp_towards')
        );
        Event::assertDispatched(UpdateCharacterSkills::class);
    }
}
