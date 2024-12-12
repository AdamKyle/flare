<?php

use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BattleMessageHandlerTest extends TestCase
{

    use RefreshDatabase;

    private ?BattleMessageHandler $battleMessageHandler;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->battleMessageHandler = resolve(BattleMessageHandler::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->battleMessageHandler = null;
    }

    public function testHandleXpForExplorationMessageWhenUserNotLoggedIn()
    {
        $user = $this->createCharacterWithUserAttributes()->getCharacter()->user;

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleXpForExplorationMessageWhenUserLoggedInAndTurnedOffSetting()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_xp_for_exploration' => false
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleXPForExplorationMessageWhenUserLoggedInAndEnabledSetting()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_xp_for_exploration' => true
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message = 'You slaughtered: 10 creatures and gained a total of: 1,000 XP.';
        });
    }

    private function createCharacterWithUserAttributes(array $attributes = [], bool $createSesion = false): CharacterFactory
    {
        $characterFactory = (new CharacterFactory())->setAttributesOnUserForCreation($attributes)->createBaseCharacter()->givePlayerLocation();

        if ($createSesion) {
            $characterFactory->createSessionForCharacter();
        }

        return $characterFactory;
    }
}
