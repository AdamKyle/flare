<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\RegisterMessage;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class RegisterMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatched_job_sends_welcome_and_global_messages(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        RegisterMessage::dispatch($character);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => str_contains($event->message, 'Welcome!'));
        Event::assertDispatched(GlobalMessageEvent::class, fn ($event) => str_contains($event->message, $character->name));
    }
}
