<?php

use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\ClassRanksMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
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

    public function testHandleFactionLoyaltyXpMessageWhenNotLoggedIn()
    {
        $user = $this->createCharacterWithUserAttributes()->getCharacter()->user;

        $this->battleMessageHandler->handleFactionLoyaltyXp($user, 10, 1, 'npc name');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionLoyaltyXpMessageWhenLoggedInAndSettingIsTurnedOff()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_faction_loyalty_xp_gain' => false
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleFactionLoyaltyXp($user, 10, 1, 'npc name');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionLoyaltyXpMessageWhenLoggedInAndSettingIsrnabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_faction_loyalty_xp_gain' => true
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleFactionLoyaltyXp($user, 10, 1, 'npc name');

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message = 'For gaining a new fame level (1) for helping: npc name with their tasks you were rewarded with: 10 XP.';
        });
    }

    public function testHandleFactionPointGainWhenNotLoggedIn()
    {
        $user = $this->createCharacterWithUserAttributes()->getCharacter()->user;

        $this->battleMessageHandler->handleFactionPointGain($user, 10, 10, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionPointGainWhenLoggedInAndSettingIsDisabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_faction_point_message' => false
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleFactionPointGain($user, 10, 10, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionPointGainWhenLoggedInAndSettingIsEnabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_faction_point_message' => true
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleFactionPointGain($user, 10, 10, 100);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message = 'You gained: 10 Faction Points, which puts you at: 10 points. You need: 90 more points to gain a new level!';
        });
    }

    public function testHandleCurrencyGainMessageAndNotLoggedIn()
    {
        $user = $this->createCharacterWithUserAttributes([])->getCharacter()->user;

        $this->battleMessageHandler->handleCurrencyGainMessage($user, CurrenciesMessageTypes::COPPER_COINS, 10, 10);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleCurrencyGainMessageAndLoggedInWithSettingDisabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_copper_coins_per_kill' => false
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleCurrencyGainMessage($user, CurrenciesMessageTypes::COPPER_COINS, 10, 10);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleCurrencyGainMessageAndNotLoggedInWithSettingEnabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_copper_coins_per_kill' => true
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleCurrencyGainMessage($user, CurrenciesMessageTypes::COPPER_COINS, 10, 10);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message = 'You gained: 10 Copper Coins! Your new total is: 10.';
        });
    }

    public function testHandleClassRankMessageWhileLoggedOut()
    {
        $user = $this->createCharacterWithUserAttributes([])->getCharacter()->user;

        $this->battleMessageHandler->handleClassRankMessage($user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, 'Sample', 10, 10, 'Staves');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleClassRankMessageWhileLoggedInAndSettingIsDisabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_xp_for_class_masteries' => false
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleClassRankMessage($user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, 'Sample', 10, 10, 'Staves');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleClassRankMessageWhileLoggedinAndSettingIsEnabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_xp_for_class_masteries' => true
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleClassRankMessage($user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, 'Sample', 10, 10, 'Staves');

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message = 'Your class: Sample has gained experience in a weapon mastery: Staves of: 10 XP and now has a total of: 10 XP.';
        });
    }

    public function testHandleItemKillCountMessageWhenLoggedOut()
    {
        $user = $this->createCharacterWithUserAttributes([])->getCharacter()->user;

        $this->battleMessageHandler->handleItemKillCountMessage($user, 'item', 'skill name', 1, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleItemKillCountMessageWhenLoggedInAndSettingIsDisabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_item_skill_kill_count' => false
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleItemKillCountMessage($user, 'item', 'skill name', 1, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleItemKillCountMessageWhenLoggedInAndSettingIsEnabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_item_skill_kill_count' => true
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleItemKillCountMessage($user, 'item', 'skill name', 1, 100);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message = 'A item skill: skill name Attached to an item: item has gained one point towards its kill count and is now at: 1 points out of: 100. Only: 99 points left to go!';
        });
    }

    public function testHandleSkillMessageWhenLoggedOut()
    {
        $user = $this->createCharacterWithUserAttributes([])->getCharacter()->user;

        $this->battleMessageHandler->handleSkillXpUpdate($user, 'skill', 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleSkillMessageWhenLoggedInAndSettingDisabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_skill_xp_per_kill' => false
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleSkillXpUpdate($user, 'skill', 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleSkillMessageWhenLoggedInAndSettingEnabled()
    {
        $user = $this->createCharacterWithUserAttributes([
            'show_skill_xp_per_kill' => true
        ], true)->getCharacter()->user;

        $this->battleMessageHandler->handleSkillXpUpdate($user, 'skill', 100);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message = 'Your skill: skill has gained: 100 XP! Killing is the key to gaining skill experience child! kill more!';
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
