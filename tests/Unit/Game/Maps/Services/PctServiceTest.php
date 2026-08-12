<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Flare\Models\Character;
use App\Game\Maps\Services\PctService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class PctServiceTest extends TestCase
{
    use CreateCharacterAutomation, RefreshDatabase;

    private ?Character $character = null;

    private ?PctService $pctService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->pctService = resolve(PctService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->pctService = null;
    }

    public function test_use_pct_sends_restriction_message_and_returns_false_when_automation_is_running(): void
    {
        Event::fake();

        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
        ]);

        $result = $this->pctService->usePCT($this->character);

        $this->assertFalse($result);
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_use_pct_does_not_send_restriction_message_when_no_automation_is_running(): void
    {
        Event::fake();

        $result = $this->pctService->usePCT($this->character);

        $this->assertFalse($result);
        Event::assertNotDispatched(ServerMessageEvent::class);
    }
}
