<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\LoginMessage;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateFactionLoyaltyAutomationWarning;

class LoginMessageTest extends TestCase
{
    use CreateEvent, CreateFactionLoyaltyAutomationWarning, RefreshDatabase;

    public function test_dispatched_job_sends_welcome_back_and_global_messages(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        EventFacade::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        LoginMessage::dispatch($character);

        EventFacade::assertDispatched(ServerMessageEvent::class, fn ($event) => str_contains($event->message, 'welcome back'));
        EventFacade::assertDispatched(GlobalMessageEvent::class, fn ($event) => str_contains($event->message, $character->name));
    }

    public function test_dispatched_job_announces_active_weekly_celestial_event(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->createEvent(['type' => EventType::WEEKLY_CELESTIALS, 'ends_at' => now()->addDay()]);

        EventFacade::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        LoginMessage::dispatch($character);

        EventFacade::assertDispatched(ServerMessageEvent::class, function ($event) {
            return str_contains($event->message, 'Celestials have been set free');
        });
    }

    public function test_dispatched_job_announces_unread_faction_loyalty_warning(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->createFactionLoyaltyAutomationWarning(['character_id' => $character->id, 'message' => 'Your loyalty automation was paused.']);

        EventFacade::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        LoginMessage::dispatch($character);

        EventFacade::assertDispatched(ServerMessageEvent::class, function ($event) {
            return str_contains($event->message, 'Your loyalty automation was paused.');
        });
    }
}
