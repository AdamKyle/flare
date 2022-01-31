<?php

namespace App\Game\Battle\Handlers;


use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\Monster;
use App\Game\Core\Values\FactionLevel;
use App\Game\Core\Values\FactionType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class FactionHandler {

    public function handleFaction(Character $character, Monster $monster) {
        $this->handleFactionPoints($character, $monster);
    }

    protected function handleFactionPoints(Character $character, Monster $monster) {
        $mapId   = $monster->gameMap->id;
        $mapName = $monster->gameMap->name;
        $faction = $character->factions()->where('game_map_id', $mapId)->first();

        $faction->current_points += FactionLevel::gatPointsPerLevel($faction->current_level);

        if ($faction->current_points > $faction->points_needed) {
            $faction->current_points = $faction->points_needed;
        }

        if ($faction->current_points === $faction->points_needed && !FactionLevel::isMaxLevel($faction->current_level, $faction->current_points)) {

            return $this->handleFactionLevelUp($character, $faction, $mapName);

        } else if (FactionLevel::isMaxLevel($faction->current_level, $faction->current_points) && !$faction->maxed) {

            return $this->handleFactionMaxedOut($character, $faction, $mapName);
        }

        $faction->save();
    }

    protected function handleFactionLevelUp(Character $character, Faction $faction, string $mapName) {
        event(new ServerMessageEvent($character->user, $mapName . ' faction has gained a new level!'));

        $faction = $this->updateFaction($faction);

        $this->rewardPlayer($character, $faction, $mapName, FactionType::getTitle($faction->current_level));

        event(new ServerMessageEvent($character->user, 'Achieved title: ' . FactionType::getTitle($faction->current_level) . ' of ' . $mapName));
    }

    protected function handleFactionMaxedOut(Character $character, Faction $faction, string $mapName) {
        event(new ServerMessageEvent($character->user, $mapName . ' faction has become maxed out!'));
        event(new GlobalMessageEvent($character->name . 'Has maxed out the faction for: ' . $mapName . ' They are considered legendary among the people of this land.'));

        $this->rewardPlayer($character, $faction, $mapName, FactionType::getTitle($faction->current_level));

        $faction->update([
            'maxed' => true,
        ]);
    }
}
