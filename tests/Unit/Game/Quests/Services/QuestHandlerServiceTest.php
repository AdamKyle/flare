<?php

namespace Tests\Unit\Game\Quests\Services;

use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Quests\Services\QuestHandlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class QuestHandlerServiceTest extends TestCase
{
    use CreateNpc, CreateQuest, RefreshDatabase;

    public function testHandInQuestResponseIncludesCompletedQuestInCompletedQuestsList(): void
    {
        Event::fake();

        $npc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gold' => 200]);

        $result = resolve(QuestHandlerService::class)->handInQuest($character->refresh(), $quest);

        $this->assertEquals(200, $result['status']);
        $this->assertTrue(in_array($quest->id, $result['completed_quests']->toArray()));
    }

    public function testHandInQuestCompletesQuestSynchronouslyWithoutRequiringRefresh(): void
    {
        Event::fake();

        $npc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gold' => 200]);

        resolve(QuestHandlerService::class)->handInQuest($character->refresh(), $quest);

        $this->assertEquals(1, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
    }

    public function testShouldBailOnQuestReturnsFalseWhenCharacterHasAssistingNpcAtRequiredFameLevel(): void
    {
        $questNpc = $this->createNpc();
        $assistingNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => $assistingNpc->id,
            'required_fame_level' => 3,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->first();

        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);

        FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $assistingNpc->id,
            'current_level' => 5,
        ]);

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertFalse($result);
    }

    public function testShouldBailOnQuestReturnsTrueWhenCharacterHasFameOnQuestGiverButNotAssistingNpc(): void
    {
        $questNpc = $this->createNpc();
        $assistingNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => $assistingNpc->id,
            'required_fame_level' => 3,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->first();

        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);

        FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $questNpc->id,
            'current_level' => 5,
        ]);

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertTrue($result);
    }

    public function testShouldBailOnQuestReturnsTrueWhenCharacterIsBelowRequiredFameLevelForAssistingNpc(): void
    {
        $questNpc = $this->createNpc();
        $assistingNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => $assistingNpc->id,
            'required_fame_level' => 10,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->first();

        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);

        FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $assistingNpc->id,
            'current_level' => 3,
        ]);

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertTrue($result);
    }

    public function testShouldBailOnQuestReturnsFalseWhenQuestHasNoAssistingNpcRequirement(): void
    {
        $questNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => null,
            'required_fame_level' => null,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertFalse($result);
    }
}
