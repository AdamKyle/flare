<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\PassiveSkill;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Npc;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;

class NpcKingdomHandler {

    use KingdomCache;

    private $npcServerMessageBuilder;

    private const KINGDOM_COST = 10000;

    public function __construct(NpcServerMessageBuilder $npcServerMessageBuilder) {
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    public function takeKingdom(Character $character, Npc $npc): bool {

        if (!is_null($character->can_settle_again_at)) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('kingdom_time_out', $npc), true));

            event(new ServerMessageEvent($character->user, 'You cannot settle another kingdom for: ' . now()->diffInMinutes($character->can_settle_again_at) . ' Minutes.'));

            return false;
        }

        $characterX     = $character->map->character_position_x;
        $characterY     = $character->map->character_position_y;
        $characterMapId = $character->map->game_map_id;

        $kingdom = Kingdom::whereNull('character_id')
                          ->where('x_position', $characterX)
                          ->where('y_position', $characterY)
                          ->where('game_map_id', $characterMapId)
                          ->where('npc_owned', true)
                          ->first();

        if (!is_null($kingdom)) {
            if ($this->handleGold($character)) {
                $kingdom->update([
                    'character_id' => $character->id,
                    'npc_owned'    => false,
                    'last_walked'  => now(),
                ]);

                $this->handleKingdomBuildings($character, $kingdom);

                $this->updateKingdom($character->refresh(), $kingdom->refresh());

                return true;
            }
        }


        return false;
    }

    protected function handleGold(Character $character) {
        $gold         = $character->gold;
        $kingdomCount = $character->kingdoms()->where('game_map_id', $character->map->game_map_id)->count();
        $cost         = ($kingdomCount * self::KINGDOM_COST);

        if ($gold < $cost) {
            broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('not_enough_gold', $npc), true));

            return false;
        }

        $newGold = $character->gold - $cost;

        $character->update([
            'gold' => $newGold,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        return true;
    }

    protected function handleKingdomBuildings(Character $character, Kingdom $kingdom) {
        foreach ($kingdom->buildings as $building) {
            $passive = PassiveSkill::where('name', $building->name)->first();

            if (!is_null($passive)) {
                $characterPassive = $character->passiveSkills()->where('passive_skill_id', $passive->id)->first();

                if (!is_null($characterPassive)) {
                    if ($characterPassive->is_locked && $characterPassive->level < 1) {
                        $building->update([
                            'is_locked' => true,
                        ]);

                        event(new ServerMessageEvent($character->user, $building->name . ' has been locked, as you do not meet the passive skill requirements.'));
                    } else {
                        $building->update([
                            'is_locked' => false,
                        ]);

                        event(new ServerMessageEvent($character->user, $building->name . ' has been unlocked, as you meet the passive skill requirements.'));
                    }
                }
            }
        }
    }

    protected function updateKingdom(Character $character, Kingdom $kingdom) {
        $this->addKingdomToCache($character, $kingdom);

        event(new AddKingdomToMap($character));
        event(new UpdateGlobalMap($character));
        event(new UpdateNPCKingdoms($character->map->gameMap));
    }
}
