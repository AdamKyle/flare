<?php

namespace Tests\Unit\Game\Automation\Middleware;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;
use App\Game\Automation\Middleware\IsCharacterExploring;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class IsCharacterExploringTest extends TestCase
{
    use RefreshDatabase;

    private Character $character;

    private IsCharacterExploring $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->getCharacter();

        $this->middleware = new IsCharacterExploring();

        $this->actingAs($this->character->user);
    }

    public function test_handle_continues_json_request_when_character_is_not_exploring(): void
    {
        Event::fake();

        $response = $this->middleware->handle($this->jsonRequest(), function () {
            return response()->json([
                'message' => 'continued',
            ]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('continued', json_decode($response->getContent(), true)['message']);
    }

    public function test_handle_returns_json_response_when_character_is_exploring(): void
    {
        Event::fake();

        $this->createCharacterAutomation();

        $response = $this->middleware->handle($this->jsonRequest(), function () {
            return response()->json([
                'message' => 'continued',
            ]);
        });

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            'You are too busy to do that. You are currently exploring. You can still sell items from your inventory.',
            $jsonData['message']
        );
    }

    public function test_handle_dispatches_server_message_event_for_json_request_when_character_is_exploring(): void
    {
        Event::fake();

        $this->createCharacterAutomation();

        $this->middleware->handle($this->jsonRequest(), function () {
            return response()->json([
                'message' => 'continued',
            ]);
        });

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_handle_continues_web_request_when_character_is_not_exploring(): void
    {
        Event::fake();

        $response = $this->middleware->handle($this->webRequest(), function () {
            return response('continued');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('continued', $response->getContent());
    }

    public function test_handle_redirects_web_request_when_character_is_exploring(): void
    {
        Event::fake();

        $this->createCharacterAutomation();

        $response = $this->middleware->handle($this->webRequest(), function () {
            return response('continued');
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_handle_dispatches_server_message_event_for_web_request_when_character_is_exploring(): void
    {
        Event::fake();

        $this->createCharacterAutomation();

        $this->middleware->handle($this->webRequest(), function () {
            return response('continued');
        });

        Event::assertDispatched(ServerMessageEvent::class);
    }

    private function jsonRequest(): Request
    {
        return Request::create('/test', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }

    private function webRequest(): Request
    {
        return Request::create('/test', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);
    }

    private function createCharacterAutomation(): CharacterAutomation
    {
        return CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
    }
}
