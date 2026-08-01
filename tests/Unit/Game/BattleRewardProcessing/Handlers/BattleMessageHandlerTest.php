<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\ClassRanksMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserSession;

class BattleMessageHandlerTest extends TestCase
{
    use CreateUser, CreateUserSession, RefreshDatabase;

    private ?BattleMessageHandler $battleMessageHandler;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([ServerMessageEvent::class]);
        $this->battleMessageHandler = resolve(BattleMessageHandler::class);
    }

    protected function tearDown(): void
    {
        $this->battleMessageHandler = null;

        parent::tearDown();
    }

    public function test_messages_are_not_dispatched_for_logged_out_users(): void
    {
        $this->battleMessageHandler->handleMessageForExplorationXp($this->createUser(), 10, 1_000);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_messages_are_not_dispatched_when_the_user_setting_is_disabled(): void
    {
        $user = $this->createUserSession($this->createUser(['show_xp_for_exploration' => false]));

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_exploration_xp_message_contract(): void
    {
        $user = $this->createUserSession($this->createUser(['show_xp_for_exploration' => true]));

        $this->battleMessageHandler->handleMessageForExplorationXp($user, 10, 1_000);

        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'You slaughtered: 10 creatures and gained a total of: 1,000 XP.');
    }

    public function test_faction_loyalty_xp_message_contract(): void
    {
        $user = $this->createUserSession($this->createUser(['show_faction_loyalty_xp_gain' => true]));

        $this->battleMessageHandler->handleFactionLoyaltyXp($user, 10, 1, 'npc name');

        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'For gaining a new fame level (1) for helping: npc name with their tasks you were rewarded with: 10 XP.');
    }

    public function test_faction_point_message_contract(): void
    {
        $user = $this->createUserSession($this->createUser(['show_faction_point_message' => true]));

        $this->battleMessageHandler->handleFactionPointGain($user, 10, 10, 100);

        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'You gained: 10 Faction Points, which puts you at: 10 points. You need: 90 more points to gain a new level!');
    }

    public function test_currency_message_contract(): void
    {
        $user = $this->createUserSession($this->createUser(['show_copper_coins_per_kill' => true]));

        $this->battleMessageHandler->handleCurrencyGainMessage($user, CurrenciesMessageTypes::COPPER_COINS, 10, 10);

        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'You gained: 10 Copper Coins! Your new total is: 10.');
    }

    public function test_class_rank_message_contract(): void
    {
        $user = $this->createUserSession($this->createUser(['show_xp_for_class_masteries' => true]));

        $this->battleMessageHandler->handleClassRankMessage($user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, 'Sample', 10, 10, 'Staves');

        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'Your class: Sample has gained experience in a weapon mastery: Staves of: 10 XP and now has a total of: 10 XP.');
    }

    public function test_item_kill_count_message_contract(): void
    {
        $user = $this->createUserSession($this->createUser(['show_item_skill_kill_count' => true]));

        $this->battleMessageHandler->handleItemKillCountMessage($user, 'item', 'skill name', 1, 100);

        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'A item skill: skill name Attached to an item: item has gained one point towards its kill count and is now at: 1 points out of: 100. Only: 99 points left to go!');
    }

    public function test_skill_xp_message_contract(): void
    {
        $user = $this->createUserSession($this->createUser(['show_skill_xp_per_kill' => true]));

        $this->battleMessageHandler->handleSkillXpUpdate($user, 'skill', 100);

        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'Your skill: skill has gained: 100 XP! Killing is the key to gaining skill experience child! kill more!');
    }
}
