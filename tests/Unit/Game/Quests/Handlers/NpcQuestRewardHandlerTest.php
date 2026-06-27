<?php

namespace Tests\Unit\Game\Quests\Handlers;

use App\Flare\Values\FeatureTypes;
use App\Game\Factions\FactionLoyalty\Services\UpdateFactionLoyaltyService;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class NpcQuestRewardHandlerTest extends TestCase
{
    use CreateNpc, CreateQuest, RefreshDatabase;

    public function test_extend_sets_quest_grants_extra_sets_to_correct_character(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $initialSetCount = $character->inventorySets()->count();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::EXTEND_SETS,
            'unlocks_skill' => false,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
            'reward_item' => null,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $handler = resolve(NpcQuestRewardHandler::class);
        $handler->processReward($quest, $npc, $character);

        $character = $character->refresh();
        $this->assertEquals($initialSetCount + 10, $character->inventorySets()->count());
    }

    public function test_capital_cities_feature_reward_sends_unlock_messages_without_creating_stored_state(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::CAPITAL_CITIES,
            'unlocks_skill' => false,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
            'reward_item' => null,
        ]);

        resolve(NpcQuestRewardHandler::class)->processReward($quest, $npc, $character);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event): bool {
            return $event->message === 'You have unlocked access to Capital Cities.';
        });
        Event::assertDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($character): bool {
            return $event->message === $character->name.' has unlocked access to Capital Cities!';
        });
        $this->assertEquals(0, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
    }

    public function test_capital_city_gold_bars_feature_reward_sends_unlock_messages_without_creating_stored_state(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::CAPITAL_CITY_GOLD_BARS,
            'unlocks_skill' => false,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
            'reward_item' => null,
        ]);

        resolve(NpcQuestRewardHandler::class)->processReward($quest, $npc, $character);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event): bool {
            return $event->message === 'You have unlocked Gold Bar management for your Capital City.';
        });
        Event::assertDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($character): bool {
            return $event->message === $character->name.' has unlocked Capital City Gold Bar management!';
        });
        $this->assertEquals(0, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
    }

    public function test_quest_xp_is_additive(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'xp' => 25,
            'xp_next' => 1000,
        ]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'reward_xp' => 50,
        ]);

        resolve(NpcQuestRewardHandler::class)->processXpReward($quest, $character->refresh());

        $this->assertEquals(75, $character->refresh()->xp);
    }

    public function test_process_reward_processes_non_xp_rewards_before_xp(): void
    {
        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);
        $handler = Mockery::mock(
            NpcQuestRewardHandler::class,
            [
                resolve(NpcServerMessageBuilder::class),
                resolve(UpdateFactionLoyaltyService::class),
            ],
        )->makePartial();

        $handler->shouldReceive('processNonXpRewards')
            ->once()
            ->with($quest, $npc, $character)
            ->ordered();
        $handler->shouldReceive('processXpReward')
            ->once()
            ->with($quest, $character)
            ->ordered();

        $handler->processReward($quest, $npc, $character);
    }

    public function test_process_non_xp_rewards_does_not_award_xp(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'xp' => 25,
            'xp_next' => 1000,
        ]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'reward_xp' => 50,
            'reward_item' => null,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'unlocks_skill' => false,
        ]);

        resolve(NpcQuestRewardHandler::class)->processNonXpRewards($quest, $npc, $character->refresh());

        $this->assertEquals(25, $character->refresh()->xp);
    }

    public function test_process_xp_reward_awards_xp_and_processes_level_up(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'level' => 1,
            'xp' => 99,
            'xp_next' => 100,
        ]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'reward_xp' => 1,
        ]);

        resolve(NpcQuestRewardHandler::class)->processXpReward($quest, $character->refresh());

        $this->assertEquals(2, $character->refresh()->level);
    }
}
