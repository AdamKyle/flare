<?php

namespace Tests\Unit\Game\Messages\Handlers;

use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcTypes;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Handlers\NpcCommandHandler;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class NpcCommandHandlerTest extends TestCase {
    use RefreshDatabase, CreateItem, CreateNpc, CreateQuest, CreateGameSkill;

    private $item;

    private $character;

    private $npckingdom;

    private $questNpc;

    private $kingdomHolderNPC;

    private $conjureNpc;

    private $quest;

    private $rewardItem;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'type' => 'quest'
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->assignFactionSystem()
                                                 ->assignSkill(
                                                     $this->createGameSkill([
                                                         'type'      => SkillTypeValue::ALCHEMY,
                                                         'is_locked' => true,
                                                     ]),
                                                     1, true
                                                 )
                                                 ->kingdomManagement()
                                                 ->assignKingdom([
                                                     'x_position' => 16,
                                                     'y_position' => 16,
                                                 ])
                                                 ->assignBuilding()
                                                 ->assignUnits()
                                                 ->getCharacterFactory()
                                                 ->inventoryManagement()
                                                 ->giveItem($this->item);

        $npcKingdom = $this->character->getCharacter(false)->kingdoms()->first();

        $npcKingdom->update([
            'character_id' => null,
            'npc_owned'    => true,
        ]);

        $this->npcKingdom = $npcKingdom->refresh();

        $this->kingdomHolderNPC = $this->createNpc(['type' => NpcTypes::KINGDOM_HOLDER, 'name' => 'NPC', 'real_name' => 'NPC']);
        $this->questNpc         = $this->createNpc(['type' => NpcTypes::QUEST_GIVER, 'name' => 'NPC 2', 'real_name' => 'NPC 2']);
        $this->conjureNpc       = $this->createNpc(['type' => NpcTypes::SUMMONER, 'name' => 'NPC 3', 'real_name' => 'NPC 3']);

        $this->rewardItem       = $this->createItem(['name' => 'Apples', 'type' => 'quest']);

        $this->quest = $this->createQuest([
            'npc_id'             => $this->questNpc->id,
            'item_id'            => $this->item,
            'name'               => 'Quest Name',
            'reward_gold'        => 1000,
            'reward_gold_dust'   => 1000,
            'reward_xp'          => 25,
            'reward_shards'      => 1000,
            'unlocks_skill'      => true,
            'unlocks_skill_type' => SkillTypeValue::ALCHEMY,
            'reward_item'        => $this->rewardItem->id,
            'gold_cost'          => 0,
            'shard_cost'         => 0,
            'gold_dust_cost'     => 0,
        ]);

        Event::fake();
    }

    public function testCharacterIsDead() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->updateCharacter([
            'is_dead' => true,
        ])->getUser()->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::TAKE_KINGDOM, $this->kingdomHolderNPC, $user);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCharacterIsAdventuring() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->updateCharacter([
            'can_adventure' => false,
        ])->getUser()->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::TAKE_KINGDOM, $this->kingdomHolderNPC, $user);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCharacterIsNotAtSameLocation() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $this->kingdomHolderNPC->update([
            'x_position'               => 100,
            'y_position'               => 100,
            'must_be_at_same_location' => true,
        ]);

        $kingdomHolderNpc = $this->kingdomHolderNPC->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::TAKE_KINGDOM, $kingdomHolderNpc, $user);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCharacterTakesKingdom() {
        $user = $this->character->getCharacterFactory()->getUser();

        $this->assertCount(0, $user->character->kingdoms);

        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $npcCommandHandler->handleForType(NpcCommandTypes::TAKE_KINGDOM, $this->kingdomHolderNPC, $user);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertCount(1, $user->character->kingdoms);
    }

    public function testCharacterCannotAffordKingdom() {
        $user = $this->character->getCharacterFactory()->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 32,
        ])->assignBuilding()->assignUnits()->getUser();

        $this->assertCount(1, $user->character->kingdoms);

        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $npcCommandHandler->handleForType(NpcCommandTypes::TAKE_KINGDOM, $this->kingdomHolderNPC, $user);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertCount(1, $user->character->kingdoms);
    }

    public function testCharacterCanInteractWithConjureNpc() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $npcCommandHandler->handleForType(NpcCommandTypes::CONJURE, $this->conjureNpc, $user);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(NpcComponentShowEvent::class);
    }

    public function testCharacterHandlesQuest() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);


        $this->assertCount(1, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertGreaterThan(100, $character->gold);
        $this->assertGreaterThan(100, $character->gold_dust);
        $this->assertGreaterThan(100, $character->shards);

        $item = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id = $this->rewardItem->id && $slot->item->type === 'quest';
        })->first();

        $this->assertNotNull($item);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNotNull($skill);
    }

    public function testCharacterHandlesQuestWithPlaneRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $item = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::HELL
        ]);

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $user = $this->character->giveItem($item)->getCharacterFactory()->getUser();

        $this->quest->delete();

        $this->createQuest([
            'access_to_map_id' => $gameMap->id,
            'npc_id'           => $this->questNpc->id,
            'gold_cost'        => 0,
            'shard_cost'       => 0,
            'gold_dust_cost'   => 0,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(1, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertGreaterThan(10, $character->gold);
        $this->assertGreaterThan(10, $character->gold_dust);
        $this->assertGreaterThan(10, $character->shards);
    }

    public function testCharacterHandlesQuestWithoutPlaneRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $item = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::HELL
        ]);

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $user = $this->character->getCharacterFactory()->getUser();

        $this->quest->delete();

        $this->createQuest([
            'access_to_map_id' => $gameMap->id,
            'npc_id'           => $this->questNpc->id,
            'gold_cost'        => 0,
            'shard_cost'       => 0,
            'gold_dust_cost'   => 0,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());
    }

    public function testCharacterHandlesQuestWithSecondaryItemRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $item = $this->createItem(['type' => 'quest', 'name' => 'some item']);

        $user = $this->character->giveItem($item)->getCharacterFactory()->completeQuest($this->quest)->getUser();

        $this->createQuest([
            'secondary_required_item' => $item->id,
            'npc_id'                  => $this->questNpc->id,
            'gold_cost'               => 0,
            'shard_cost'              => 0,
            'gold_dust_cost'          => 0,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(2, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertGreaterThan(10, $character->gold);
        $this->assertGreaterThan(10, $character->gold_dust);
        $this->assertGreaterThan(10, $character->shards);

        $item = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id = $this->rewardItem->id && $slot->item->type === 'quest';
        })->first();

        $this->assertNotNull($item);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNotNull($skill);
    }

    public function testCharacterHandlesQuestWithoutSecondaryItemRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $item = $this->createItem(['type' => 'quest', 'name' => 'some item']);

        $user = $this->character->getCharacterFactory()->completeQuest($this->quest)->getUser();

        $this->createQuest([
            'secondary_required_item' => $item->id,
            'npc_id'                  => $this->questNpc->id,
            'gold_cost'               => 0,
            'shard_cost'              => 0,
            'gold_dust_cost'          => 0,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(1, QuestsCompleted::all());
    }

    public function testCharacterHandlesQuestWithoutParentRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $item = $this->createItem(['type' => 'quest', 'name' => 'some item']);

        $user = $this->character->getCharacterFactory()->getUser();

        $this->createQuest([
            'parent_quest_id'         => $this->quest->id,
            'secondary_required_item' => $item->id,
            'npc_id'                  => $this->questNpc->id,
            'gold_cost'               => 0,
            'shard_cost'              => 0,
            'gold_dust_cost'          => 0,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        // The player will complete the first quest but not the second.
        $this->assertLessThan(2, QuestsCompleted::count());
    }

    public function testCharacterHandlesQuestWithFactionRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $character = $user->character;

        $character->factions()->where('game_map_id', $character->map->gameMap->id)->first()->update([
            'current_level'  => 4,
            'maxed'          => true,
            'current_points' => 8000
        ]);

        $this->quest->delete();

        $this->createQuest([
            'npc_id'                  => $this->questNpc->id,
            'gold_cost'               => 0,
            'shard_cost'              => 0,
            'gold_dust_cost'          => 0,
            'faction_game_map_id'     => $character->map->gameMap->id,
            'required_faction_level'  => 1,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user->refresh());

        // The player will complete the first quest but not the second.
        $this->assertEquals(1, QuestsCompleted::count());
    }

    public function testCharacterHandlesQuestWithFactionMaxRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $character = $user->character;

        $character->factions()->where('game_map_id', $character->map->gameMap->id)->first()->update([
            'current_level'  => 4,
            'maxed'          => true,
            'current_points' => 8000
        ]);

        $this->quest->delete();

        $this->createQuest([
            'npc_id'                  => $this->questNpc->id,
            'gold_cost'               => 0,
            'shard_cost'              => 0,
            'gold_dust_cost'          => 0,
            'faction_game_map_id'     => $character->map->gameMap->id,
            'required_faction_level'  => 5,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user->refresh());

        // The player will complete the first quest but not the second.
        $this->assertEquals(1, QuestsCompleted::count());
    }

    public function testCharacterHandlesQuestWithoutFactionRequirement() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $character = $user->character;

        $character->factions()->where('game_map_id', $character->map->gameMap->id)->first()->update([
            'current_level'  => 0,
            'maxed'          => false,
            'current_points' => 0
        ]);

        $this->quest->delete();

        $this->createQuest([
            'npc_id'                  => $this->questNpc->id,
            'gold_cost'               => 0,
            'shard_cost'              => 0,
            'gold_dust_cost'          => 0,
            'faction_game_map_id'     => $character->map->gameMap->id,
            'required_faction_level'  => 1,
        ]);

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user->refresh());

        // The player will complete the first quest but not the second.
        $this->assertEquals(0, QuestsCompleted::count());
    }

    public function testCharacterHandlesQuestNotEnoughToPay() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $this->quest->update([
            'gold_cost'      => 100000,
            'shard_cost'     => 100000,
            'gold_dust_cost' => 100000
        ]);

        $user = $this->character->getCharacterFactory()->getUser();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->gold_dust);
        $this->assertLessThan(100, $character->shards);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNull($skill);
    }

    public function testCharacterHandlesQuestHasEnoughToPay() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $this->quest->update([
            'gold_cost'      => 100000,
            'shard_cost'     => 100000,
            'gold_dust_cost' => 100000
        ]);

        $this->character->getCharacterFactory()->updateCharacter([
            'gold'      => 1000000000,
            'shards'    => 1000000000,
            'gold_dust' => 1000000000,
        ]);

        $user = $this->character->getCharacterFactory()->getUser();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(1, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertGreaterThan(100, $character->gold);
        $this->assertGreaterThan(100, $character->gold_dust);
        $this->assertGreaterThan(100, $character->shards);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNotNull($skill);
    }

    public function testCharacterHandlesQuestDoesntHaveItem() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $user->character->inventory->slots()->delete();

        $user = $user->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->gold_dust);
        $this->assertLessThan(100, $character->shards);

        $item = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id = $this->rewardItem->id && $slot->item->type === 'quest';
        })->first();

        $this->assertNull($item);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNull($skill);
    }


    public function testCharacterHandlesQuestDoesntInventorySpace() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->updateCharacter([
            'inventory_max' => 0
        ])->getUser();

        $user = $user->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->gold_dust);
        $this->assertLessThan(100, $character->shards);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNull($skill);
    }

    public function testCharacterHandlesQuestGoldCapped() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->updateCharacter([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ])->getUser();

        $user = $user->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold_dust);
        $this->assertLessThan(100, $character->shards);
    }

    public function testCharacterHandlesQuestGoldDustCapped() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->updateCharacter([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ])->getUser();

        $user = $user->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->shards);
    }

    public function testCharacterHandlesQuestShardsCapped() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->updateCharacter([
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ])->getUser();

        $user = $user->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->gold_dust);
    }

    public function testHasNoQuests() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $user = $this->character->getCharacterFactory()->getUser();

        $user->character->questsCompleted()->create([
            'character_id' => $user->character->id,
            'quest_id'     => $this->quest->id,
        ]);

        $user->character->inventory->slots()->delete();

        $user = $user->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(1, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->gold_dust);
        $this->assertLessThan(100, $character->shards);

        $item = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id = $this->rewardItem->id && $slot->item->type === 'quest';
        })->first();

        $this->assertNull($item);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNull($skill);
    }

    public function testCharacterCannotHaveRewardFromQuestWithNoRequiredItem() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $this->quest->update([
            'item_id' => null,
        ]);

        $user = $this->character->getCharacterFactory()->updateCharacter([
            'inventory_max' => 0
        ])->getUser();

        $user = $user->refresh();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->gold_dust);
        $this->assertLessThan(100, $character->shards);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNull($skill);
    }

    public function testCharacterCannotAffordRewardFromQuestWithNoRequiredItem() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $this->quest->update([
            'gold_cost'      => 100000,
            'shard_cost'     => 100000,
            'gold_dust_cost' => 100000,
            'item_id'        => null,
        ]);

        $user = $this->character->getCharacterFactory()->getUser();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertLessThan(100, $character->gold);
        $this->assertLessThan(100, $character->gold_dust);
        $this->assertLessThan(100, $character->shards);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNull($skill);
    }

    public function testCharacterDoesNotHaveTheShardsFromQuestWithNoRequiredItem() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $this->quest->update([
            'gold_cost'      => 100000,
            'shard_cost'     => 100000,
            'gold_dust_cost' => 100000,
            'item_id'        => null,
        ]);

        $this->character->getCharacterFactory()->updateCharacter([
            'gold'      => 1000000000,
            'gold_dust' => 1000000000,
            'shards'    => 0
        ]);

        $user = $this->character->getCharacterFactory()->getUser();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(0, QuestsCompleted::all());

        $character = $user->character->refresh();

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNull($skill);
    }

    public function testCharacterCanHaveRewardFromQuestWithNoRequiredItem() {
        $npcCommandHandler = resolve(NpcCommandHandler::class);

        $this->quest->update([
            'gold_cost'      => 100000,
            'shard_cost'     => 100000,
            'gold_dust_cost' => 100000,
            'item_id'        => null,
        ]);

        $this->character->getCharacterFactory()->updateCharacter([
            'gold'      => 1000000000,
            'shards'    => 1000000000,
            'gold_dust' => 1000000000,
        ]);

        $user = $this->character->getCharacterFactory()->getUser();

        $npcCommandHandler->handleForType(NpcCommandTypes::QUEST, $this->questNpc, $user);

        $this->assertCount(1, QuestsCompleted::all());

        $character = $user->character->refresh();

        $this->assertGreaterThan(100, $character->gold);
        $this->assertGreaterThan(100, $character->gold_dust);
        $this->assertGreaterThan(100, $character->shards);

        $skill = $character->skills->filter(function($skill) {
            return !$skill->is_locked && $skill->type()->isAlchemy();
        })->first();

        $this->assertNotNull($skill);
    }
}
