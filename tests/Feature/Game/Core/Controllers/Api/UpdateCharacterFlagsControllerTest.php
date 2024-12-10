<?php

namespace Tests\Feature\Game\Core\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Models\Character;
use App\Game\GuideQuests\Events\OpenGuideQuestModal;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;

class UpdateCharacterFlagsControllerTest extends TestCase
{
    use RefreshDatabase, CreateGuideQuest;

    private ?Character $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testTurnOffIntroSlides()
    {
        Event::fake();

        $this->character->user()->update([
            'show_intro_page' => true
        ]);

        $character = $this->character->refresh();

        $this->actingAs($this->character->user)
            ->call('POST', '/api/update-player-flags/turn-off-intro/' . $character->id);

        $character = $this->character->refresh();

        Event::assertDispatched(OpenGuideQuestModal::class);

        $this->assertFalse(($character->user->show_intro_page));
    }

    public function testTurnOffIntroSlidesWhenWeHaveAGuideQuest()
    {
        Event::fake();

        $this->character->user()->update([
            'show_intro_page' => true
        ]);

        $character = $this->character->refresh();

        $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $this->actingAs($this->character->user)
            ->call('POST', '/api/update-player-flags/turn-off-intro/' . $character->id);

        $character = $this->character->refresh();

        Event::assertDispatched(OpenGuideQuestModal::class);

        $this->assertFalse(($character->user->show_intro_page));
    }

    public function testTurnOffIntroSlidesWhenWeHaveAGuideQuestAndDoNotFakeEvent()
    {

        $this->character->user()->update([
            'show_intro_page' => true
        ]);

        $character = $this->character->refresh();

        $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $this->actingAs($this->character->user)
            ->call('POST', '/api/update-player-flags/turn-off-intro/' . $character->id);

        $character = $this->character->refresh();

        $this->assertFalse(($character->user->show_intro_page));
    }
}
