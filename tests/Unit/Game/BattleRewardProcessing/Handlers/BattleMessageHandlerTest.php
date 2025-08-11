<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\User;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\ClassRanksMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class BattleMessageHandlerTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    private ?BattleMessageHandler $battleMessageHandler;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake([ServerMessageEvent::class]);

        $this->battleMessageHandler = resolve(BattleMessageHandler::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->battleMessageHandler = null;
    }

    public function testHandleXpForExplorationMessageWhenUserNotLoggedIn()
    {
        $user = $this->createUserWithOptionalSession();

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleXpForExplorationMessageWhenUserLoggedInAndTurnedOffSetting()
    {
        $user = $this->createUserWithOptionalSession([
            'show_xp_for_exploration' => false,
        ], true);

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleXPForExplorationMessageWhenUserLoggedInAndEnabledSetting()
    {
        $user = $this->createUserWithOptionalSession([
            'show_xp_for_exploration' => true,
        ], true);

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertDispatched(
            ServerMessageEvent::class,
            fn (ServerMessageEvent $event) =>
                $event->message === 'You slaughtered: 10 creatures and gained a total of: 1,000 XP.'
        );
    }

    public function testHandleFactionLoyaltyXpMessageWhenNotLoggedIn()
    {
        $user = $this->createUserWithOptionalSession();

        $this->battleMessageHandler->handleFactionLoyaltyXp($user, 10, 1, 'npc name');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionLoyaltyXpMessageWhenLoggedInAndSettingIsTurnedOff()
    {
        $user = $this->createUserWithOptionalSession([
            'show_faction_loyalty_xp_gain' => false,
        ], true);

        $this->battleMessageHandler->handleFactionLoyaltyXp($user, 10, 1, 'npc name');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionLoyaltyXpMessageWhenLoggedInAndSettingIsEnabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_faction_loyalty_xp_gain' => true,
        ], true);

        $this->battleMessageHandler->handleFactionLoyaltyXp($user, 10, 1, 'npc name');

        Event::assertDispatched(
            ServerMessageEvent::class,
            fn (ServerMessageEvent $event) =>
                $event->message === 'For gaining a new fame level (1) for helping: npc name with their tasks you were rewarded with: 10 XP.'
        );
    }

    public function testHandleFactionPointGainWhenNotLoggedIn()
    {
        $user = $this->createUserWithOptionalSession();

        $this->battleMessageHandler->handleFactionPointGain($user, 10, 10, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionPointGainWhenLoggedInAndSettingIsDisabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_faction_point_message' => false,
        ], true);

        $this->battleMessageHandler->handleFactionPointGain($user, 10, 10, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleFactionPointGainWhenLoggedInAndSettingIsEnabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_faction_point_message' => true,
        ], true);

        $this->battleMessageHandler->handleFactionPointGain($user, 10, 10, 100);

        Event::assertDispatched(
            ServerMessageEvent::class,
            fn (ServerMessageEvent $event) =>
                $event->message === 'You gained: 10 Faction Points, which puts you at: 10 points. You need: 90 more points to gain a new level!'
        );
    }

    public function testHandleCurrencyGainMessageAndNotLoggedIn()
    {
        $user = $this->createUserWithOptionalSession([]);

        $this->battleMessageHandler->handleCurrencyGainMessage($user, CurrenciesMessageTypes::COPPER_COINS, 10, 10);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleCurrencyGainMessageAndLoggedInWithSettingDisabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_copper_coins_per_kill' => false,
        ], true);

        $this->battleMessageHandler->handleCurrencyGainMessage($user, CurrenciesMessageTypes::COPPER_COINS, 10, 10);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleCurrencyGainMessageAndLoggedInWithSettingEnabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_copper_coins_per_kill' => true,
        ], true);

        $this->battleMessageHandler->handleCurrencyGainMessage($user, CurrenciesMessageTypes::COPPER_COINS, 10, 10);

        Event::assertDispatched(
            ServerMessageEvent::class,
            fn (ServerMessageEvent $event) =>
                $event->message === 'You gained: 10 Copper Coins! Your new total is: 10.'
        );
    }

    public function testHandleClassRankMessageWhileLoggedOut()
    {
        $user = $this->createUserWithOptionalSession([]);

        $this->battleMessageHandler->handleClassRankMessage($user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, 'Sample', 10, 10, 'Staves');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleClassRankMessageWhileLoggedInAndSettingIsDisabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_xp_for_class_masteries' => false,
        ], true);

        $this->battleMessageHandler->handleClassRankMessage($user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, 'Sample', 10, 10, 'Staves');

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleClassRankMessageWhileLoggedInAndSettingIsEnabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_xp_for_class_masteries' => true,
        ], true);

        $this->battleMessageHandler->handleClassRankMessage($user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, 'Sample', 10, 10, 'Staves');

        Event::assertDispatched(
            ServerMessageEvent::class,
            fn (ServerMessageEvent $event) =>
                $event->message === 'Your class: Sample has gained experience in a weapon mastery: Staves of: 10 XP and now has a total of: 10 XP.'
        );
    }

    public function testHandleItemKillCountMessageWhenLoggedOut()
    {
        $user = $this->createUserWithOptionalSession([]);

        $this->battleMessageHandler->handleItemKillCountMessage($user, 'item', 'skill name', 1, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleItemKillCountMessageWhenLoggedInAndSettingIsDisabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_item_skill_kill_count' => false,
        ], true);

        $this->battleMessageHandler->handleItemKillCountMessage($user, 'item', 'skill name', 1, 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleItemKillCountMessageWhenLoggedInAndSettingIsEnabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_item_skill_kill_count' => true,
        ], true);

        $this->battleMessageHandler->handleItemKillCountMessage($user, 'item', 'skill name', 1, 100);

        Event::assertDispatched(
            ServerMessageEvent::class,
            fn (ServerMessageEvent $event) =>
                $event->message === 'A item skill: skill name Attached to an item: item has gained one point towards its kill count and is now at: 1 points out of: 100. Only: 99 points left to go!'
        );
    }

    public function testHandleSkillMessageWhenLoggedOut()
    {
        $user = $this->createUserWithOptionalSession([]);

        $this->battleMessageHandler->handleSkillXpUpdate($user, 'skill', 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleSkillMessageWhenLoggedInAndSettingDisabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_skill_xp_per_kill' => false,
        ], true);

        $this->battleMessageHandler->handleSkillXpUpdate($user, 'skill', 100);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testHandleSkillMessageWhenLoggedInAndSettingEnabled()
    {
        $user = $this->createUserWithOptionalSession([
            'show_skill_xp_per_kill' => true,
        ], true);

        $this->battleMessageHandler->handleSkillXpUpdate($user, 'skill', 100);

        Event::assertDispatched(
            ServerMessageEvent::class,
            fn (ServerMessageEvent $event) =>
                $event->message === 'Your skill: skill has gained: 100 XP! Killing is the key to gaining skill experience child! kill more!'
        );
    }

    private function createUserWithOptionalSession(array $attributes = [], bool $createSession = false): User
    {
        $user = $this->createUser($attributes);

        if ($createSession) {
            DB::table('sessions')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'testing',
                'payload' => '',
                'last_activity' => time(),
            ]);
        }

        return $user->fresh();
    }
}
