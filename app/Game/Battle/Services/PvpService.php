<?php

namespace App\Game\Battle\Services;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Jobs\RemoveKilledInPvpFromUser;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\ServerFight\Pvp\PvpAttack;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class PvpService {

    private RandomAffixGenerator $randomAffixGenerator;

    private PvpAttack $pvpAttack;

    private BattleEventHandler $battleEventHandler;

    private MapTileValue $mapTileValue;

    public function __construct(PvpAttack $pvpAttack, RandomAffixGenerator $randomAffixGenerator, BattleEventHandler $battleEventHandler, MapTileValue $mapTileValue) {
        $this->pvpAttack            = $pvpAttack;
        $this->randomAffixGenerator = $randomAffixGenerator;
        $this->battleEventHandler   = $battleEventHandler;
        $this->mapTileValue         = $mapTileValue;
    }

    public function isDefenderAtPlayersLocation(Character $attacker, Character $defender) {
        $attackerMap = $attacker->map;
        $defenderMap = $defender->map;

        $xPositionMatches = $attackerMap->character_position_x === $defenderMap->character_position_x;
        $yPositionMatches = $attackerMap->character_position_y === $defenderMap->character_position_y;
        $samePlane        = $attackerMap->game_map_id          === $defenderMap->game_map_id;

        return $xPositionMatches && $yPositionMatches && $samePlane && $defender->currentAutomations->isEmpty();
    }

    public function getHealthObject(Character $attacker, Character $defender) {
        return [
            'attacker_health' => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
            'defender_health' => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
        ];
    }

    public function attack(Character $attacker, Character $defender, string $attackType) {
        $healthObject = $this->pvpAttack->setUpPvpFight($attacker, $defender, $this->getHealthObject($attacker, $defender));

        $result = $this->pvpAttack->attackPlayer($attacker, $defender, $healthObject, $attackType);

        if ($result) {
            $this->handleReward($attacker);

            event(new ServerMessageEvent($attacker->user, 'You have killed: ' . $defender->name));
            event(new ServerMessageEvent($defender->user, 'You have been killed by: ' . $attacker->name));

            $this->pvpAttack->cache()->deleteCharacterSheet($attacker);
            $this->pvpAttack->cache()->deleteCharacterSheet($defender);

            $this->battleEventHandler->processDeadCharacter($defender);

            return;
        }
    }

    protected function handleDefenderDeath(Character $defender) {
        $defender->update([
            'killed_in_pvp' => true,
        ]);

        $defender = $this->movePlayerToNewLocation($defender);

        event(new ServerMessageEvent($defender->user, 'You were safely moved away from your current location. You cannot be targeted by pvp for 2 minutes and, during that time, your location will be masked in chat.'));

        RemoveKilledInPvpFromUser::dispatch($defender)->delay(now()->addMinutes(2));


    }

    protected function handleReward(Character $attacker) {
        $rand = rand(1, 1000000);

        if ($rand > 999995) {
            $item = $this->fetchMythicItem($attacker);

            if (!$attacker->isInventoryFull()) {
                $slot = $attacker->inventory->slots()->create([
                    'inventory_id' => $attacker->inventory->id,
                    'item_id'      => $item->id,
                ]);

                event(new GlobalMessageEvent($attacker->name . ' has found a Mythic unique!'));

                event(new ServerMessageEvent($attacker->user, 'You found: ' . $item->affix_name . ' on the enemies corpse!', $slot->id));
            }
        }
    }

    private function fetchMythicItem(Character $attacker): Item {
        $prefix = $this->randomAffixGenerator->setCharacter($attacker)
                                             ->setPaidAmount(RandomAffixDetails::MYTHIC)
                                             ->generateAffix('prefix');

        $suffix = $this->randomAffixGenerator->setCharacter($attacker)
                                             ->setPaidAmount(RandomAffixDetails::MYTHIC)
                                             ->generateAffix('suffix');

        $item = Item::inRandomOrder()->first();

        $item = $item->duplicate();

        $item->update([
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        return $item->refresh();
    }

    private function movePlayerToNewLocation(Character $character): Character {

        $cache = Cache::get('coordinates');

        $x = $cache['x'];
        $y = $cache['y'];

        $character->map()->update([
            'character_position_x' => $x[rand(0, count($x) - 1)],
            'character_position_y' => $y[rand(0, count($y) - 1)],
        ]);

        if (!$this->mapTileValue->canWalkOnWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnDeathWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnMagma($character, $character->map->character_position_x, $character->map->character_position_y) ||
            $this->mapTileValue->isPurgatoryWater($this->mapTileValue->getTileColor($character, $character->map->character_position_x, $character->map->character_position_y))
        ) {

            $character->map()->update([
                'character_position_x' => $x[rand(0, count($x) - 1)],
                'character_position_y' => $y[rand(0, count($y) - 1)],
            ]);

            return $this->movePlayerToNewLocation($character->refresh(), $cache);
        }

        return $character->refresh();
    }
}
