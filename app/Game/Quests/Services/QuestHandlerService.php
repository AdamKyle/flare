<?php

namespace App\Game\Quests\Services;

use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Events\UpdateRaidMonsters;
use App\Game\Maps\Validation\CanTravelToMap;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Quests\Handlers\NpcQuestsHandler;
use App\Game\Quests\Traits\QuestDetails;
use Exception;
use Illuminate\Support\Facades\Cache;

class QuestHandlerService {

    use QuestDetails, ResponseBuilder;

    /**
     * @var string $bailMessage
     */
    private string $bailMessage = '';

    /**
     * @var NpcQuestsHandler $npcQuestsHandler
     */
    private NpcQuestsHandler $npcQuestsHandler;

    /**
     * @var CanTravelToMap $canTravelToMap
     */
    private CanTravelToMap $canTravelToMap;

    /**
     * @var MapTileValue
     */
    private MapTileValue $mapTileValue;

    /**
     * @var BuildQuestCacheService
     */
    private BuildQuestCacheService $buildQuestCacheService;

    /**
     * @param NpcQuestsHandler $npcQuestsHandler
     * @param CanTravelToMap $canTravelToMap
     * @param MapTileValue $mapTileValue
     */
    public function __construct(
        NpcQuestsHandler $npcQuestsHandler,
        CanTravelToMap $canTravelToMap,
        MapTileValue $mapTileValue,
        BuildQuestCacheService $buildQuestCacheService
    ) {

        $this->npcQuestsHandler       = $npcQuestsHandler;
        $this->canTravelToMap         = $canTravelToMap;
        $this->mapTileValue           = $mapTileValue;
        $this->buildQuestCacheService = $buildQuestCacheService;
    }

    /**
     * Fetch the npc quest handler instance.
     *
     * @return NpcQuestsHandler
     */
    public function npcQuestsHandler(): NpcQuestsHandler {
        return $this->npcQuestsHandler;
    }

    /**
     * Should we bail on the quest?
     *
     * @param Character $character
     * @param Quest $quest
     * @return bool
     */
    public function shouldBailOnQuest(Character $character, Quest $quest): bool {
        $completedQuests = $character->questsCompleted->pluck('quest_id')->toArray();

        if (!is_null($quest->only_for_event)) {
            $event = Event::where('type', $quest->only_for_event)->first();

            if (is_null($event)) {
                $this->bailMessage = 'This quest can only be done during a special event.';

                return true;
            }
        }

        if (!$this->validateParentQuest($quest, $completedQuests)) {
            $this->bailMessage = 'You must finish the parent quest first ...';

            return true;
        }

        if ($this->questRequiresItem($quest)) {
            $foundItem = $this->fetchRequiredItem($quest, $character);

            if (is_null($foundItem)) {
                $this->bailMessage = 'You are missing a required item. Check the Required To Complete tab.';

                return true;
            }
        }

        if ($this->questRequiresSecondaryItem($quest)) {
            $secondaryItem = $this->fetchSecondaryRequiredItem($quest, $character);

            if (is_null($secondaryItem)) {
                $this->bailMessage = 'You are missing a secondary required item. Check the Required To Complete tab.';

                return true;
            }
        }

        if ($this->questHasCurrenciesRequirement($quest)) {
            if (!$this->canPay($character, $quest)) {
                $this->bailMessage = 'You don\'t have the currencies required. Check the Required To Complete tab.';

                return true;
            }
        }

        if ($this->questRequiresPlaneAccess($quest)) {
            if (!$this->hasPlaneAccess($quest, $character)) {
                $this->bailMessage = 'You do not have proper plane access to finish this quest. Check the Required To Complete tab.';

                return true;
            }
        }

        if ($this->questHasFactionRequirement($quest)) {
            if (!$this->hasMetFactionRequirement($character, $quest)) {
                $this->bailMessage = 'You are missing the required Faction Level needed to complete this quest. Check the Required To Complete tab.';

                return true;
            }
        }

        if ($this->questHasFactionLoyaltyRequirement($quest)) {
            if (!$this->hasMetFactionLoyaltyRequirements($quest, $character)) {
                $this->bailMessage = 'There is an NPC on this map who requires you to assist them with their tasks to increase their Faction Loyalty. Click the Required To Complete tab.';

                return true;
            }
        }

        if (!$this->hasCompletedRequiredQuest($character, $quest)) {
            $this->bailMessage = 'You need to complete another quest before handing this one in. Check the Required To Complete tab.';

            return true;
        }

        return false;
    }

    /**
     * @param Character $character
     * @param Npc $npc
     * @return array|Character
     */
    public function moveCharacter(Character $character, Npc $npc): array|Character {
        if ($npc->game_map_id !== $character->map->game_map_id) {
            if (!$this->canTravelToMap->canTravel($npc->game_map_id, $character)) {
                return $this->errorResult('You are missing the required quest item to travel to this NPC. Check NPC Access Requirements Section above.');
            }
        }

        $oldMapDetails = $character->map;

        $character->map()->update(['game_map_id' => $npc->game_map_id]);

        $character = $character->refresh();

        if (!$this->mapTileValue->canWalk($character, $npc->x_position, $npc->y_position)) {
            $character->map->update(['game_map_id' => $oldMapDetails->game_map_id]);

            return $this->errorResult("You can traverse to the NPC, but not move to their location as you are
            missing a required item. Click the map name under the NPC name above, to see what items you need to travel to this NPC.");
        }

        $character->map()->update([
            'character_position_x' => $npc->x_position,
            'character_position_y' => $npc->y_position,
        ]);

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        if ($oldMapDetails->gameMap ->id !== $character->map->gameMap->id) {
            event(new ServerMessageEvent($character->user, 'You were moved (at no gold cost or time out) from: ' . $oldMapDetails->gameMap->name . ' to: ' . $character->map->gameMap->name . ' in order to hand in the quest.'));
        }

        $this->updateMapDetails($character);

        return $character;
    }

    protected function updateMapDetails(Character $character): void {
        event(new UpdateMap($character->user));

        $monsters = Cache::get('monsters')[$character->map->gameMap->name];

        event(new UpdateMonsterList($monsters, $character->user));
        event(new UpdateRaidMonsters([], $character->user));
    }

    public function handInQuest(Character $character, Quest $quest) {

        try {
            $this->npcQuestsHandler()->handleNpcQuest($character, $quest);

            event(new GlobalMessageEvent($character->name . ' Has completed a quest (' . $quest->name . ') for: ' . $quest->npc->real_name . ' and been rewarded with a godly gift!'));

            $character = $character->refresh();

            $quests     = $this->buildQuestCacheService->getRegularQuests();
            $raidQuests = $this->buildQuestCacheService->fetchQuestsForRaid();

            return $this->successResult([
                'completed_quests' => $character->questsCompleted()->pluck('quest_id'),
                'player_plane'     => $character->map->gameMap->name,
                'quests'           => $quests,
                'raid_quests'      => $raidQuests,
            ]);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }
    }

    /**
     * get the bail reason.
     *
     * @return string
     */
    public function getBailMessage(): string {
        return $this->bailMessage;
    }
}
