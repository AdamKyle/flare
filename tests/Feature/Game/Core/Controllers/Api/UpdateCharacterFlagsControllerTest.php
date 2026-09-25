<?php

namespace Tests\Feature\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\GuideQuests\Events\OpenGuideQuestModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UpdateCharacterFlagsControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_turn_off_intro_slides_does_not_dispatch_guide_quest_modal(): void
    {
        Event::fake();

        $this->character->user()->update([
            'show_intro_page' => true,
        ]);

        $character = $this->character->refresh();

        $this->actingAs($this->character->user)
            ->call('POST', '/api/update-player-flags/turn-off-intro/'.$character->id);

        $character = $this->character->refresh();

        Event::assertNotDispatched(OpenGuideQuestModal::class);

        $this->assertFalse($character->user->show_intro_page);
    }
}
