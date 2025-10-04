<?php

namespace Tests\Feature\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\GuideQuests\Events\OpenGuideQuestModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;

class UpdateCharacterFlagsControllerTest extends TestCase
{
    use CreateGuideQuest, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_turn_off_intro_slides()
    {
        Event::fake();

        $this->character->user()->update([
            'show_intro_page' => true,
        ]);

        $character = $this->character->refresh();

        $this->actingAs($this->character->user)
            ->call('POST', '/api/update-player-flags/turn-off-intro/'.$character->id);

        $character = $this->character->refresh();

        Event::assertDispatched(OpenGuideQuestModal::class);

        $this->assertFalse(($character->user->show_intro_page));
    }

    public function test_turn_off_intro_slides_when_we_have_a_guide_quest()
    {
        Event::fake();

        $this->character->user()->update([
            'show_intro_page' => true,
        ]);

        $character = $this->character->refresh();

        $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $this->actingAs($this->character->user)
            ->call('POST', '/api/update-player-flags/turn-off-intro/'.$character->id);

        $character = $this->character->refresh();

        Event::assertDispatched(OpenGuideQuestModal::class);

        $this->assertFalse(($character->user->show_intro_page));
    }

    public function test_turn_off_intro_slides_when_we_have_a_guide_quest_and_do_not_fake_event()
    {

        $this->character->user()->update([
            'show_intro_page' => true,
        ]);

        $character = $this->character->refresh();

        $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $this->actingAs($this->character->user)
            ->call('POST', '/api/update-player-flags/turn-off-intro/'.$character->id);

        $character = $this->character->refresh();

        $this->assertFalse(($character->user->show_intro_page));
    }
}
