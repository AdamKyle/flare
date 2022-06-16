<?php

namespace App\Game\Battle\Services;

use Cache;
use Facades\App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Jobs\RemoveKilledInPvpFromUser;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\ServerFight\Pvp\PvpAttack;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Battle\Events\UpdateCharacterPvpAttack;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class PvpService {

    private PvpAttack $pvpAttack;

    private BattleEventHandler $battleEventHandler;

    private MapTileValue $mapTileValue;

    public function __construct(PvpAttack $pvpAttack, BattleEventHandler $battleEventHandler, MapTileValue $mapTileValue) {
        $this->pvpAttack            = $pvpAttack;
        $this->battleEventHandler   = $battleEventHandler;
        $this->mapTileValue         = $mapTileValue;
    }

    public function battleEventHandler(): BattleEventHandler {
        return $this->battleEventHandler;
    }

    public function cache(): CharacterCacheData {
        return $this->pvpAttack->cache();
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
        $cache = $this->pvpAttack->cache()->fetchPvpCacheObject($attacker, $defender);

        // We need a clean cache object.
        $this->pvpAttack->cache()->deleteCharacterSheet($attacker);
        $this->pvpAttack->cache()->deleteCharacterSheet($defender);

        if (!is_null($cache)) {
            return [
                'attacker_health'     => $cache['attacker_health'],
                'attacker_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
                'defender_health'     => $cache['defender_health'],
                'defender_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
            ];
        }

        return [
            'attacker_health'     => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
            'attacker_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
            'defender_health'     => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
            'defender_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
        ];
    }

    public function getRemainingAttackerHealth(): int {
        return $this->pvpAttack->getAttackerHealth();
    }

    public function getRemainingDefenderHealth(): int {
        return $this->pvpAttack->getDefenderHealth();
    }

    public function attack(Character $attacker, Character $defender, string $attackType, bool $ignoreSetUp = false, bool $ignoreNonWinningMessages = false): bool {

        if (!$ignoreSetUp) {
            $healthObject = $this->pvpAttack->setUpPvpFight($attacker, $defender, $this->getHealthObject($attacker, $defender));
        } else {
            $healthObject = $this->getHealthObject($attacker, $defender);
        }

        if ($healthObject['defender_health'] <= 0) {
            $this->processBattleWin($attacker, $defender, $healthObject);

            return true;
        }

        if ($healthObject['attacker_health'] <= 0) {
            $this->processBattleWin($defender, $attacker, $healthObject);

            return false;
        }

        $result = $this->pvpAttack->attackPlayer($attacker, $defender, $healthObject, $attackType);

        if ($result) {
            $this->processBattleWin($attacker, $defender, $healthObject);

            return true;
        }

        $this->updateCacheHealthForPVPFight($attacker, $defender);

        if (!$ignoreNonWinningMessages) {
            $this->updateAttackerPvpInfo($attacker, $healthObject, $defender->id, $this->pvpAttack->getDefenderHealth());
            $this->updateDefenderPvpInfo($defender, $healthObject, $attacker->id, $this->pvpAttack->getDefenderHealth());
        }

        return false;
    }

    protected function processBattleWin(Character $attacker, Character $defender, array $healthObject) {
        $this->handleReward($attacker);

        event(new ServerMessageEvent($attacker->user, 'You have killed: ' . $defender->name));
        event(new ServerMessageEvent($defender->user, 'You have been killed by: ' . $attacker->name));

        $this->battleEventHandler->processDeadCharacter($defender);

        $this->updateAttackerPvpInfo($attacker, $healthObject, $defender->id);
        $this->updateDefenderPvpInfo($defender, $healthObject, $attacker->id, $this->pvpAttack->getDefenderHealth());

        $this->pvpAttack->cache()->deleteCharacterSheet($attacker);
        $this->pvpAttack->cache()->deleteCharacterSheet($defender);

        $this->pvpAttack->cache()->removeFromPvpCache($attacker);
        $this->pvpAttack->cache()->removeFromPvpCache($defender);

        $this->handleDefenderDeath($attacker, $defender);
    }

    protected function updateCacheHealthForPVPFight(Character $attacker, Character $defender) {
        $this->pvpAttack->cache()->setPvpData($attacker, $defender, $this->pvpAttack->getAttackerHealth(), $this->pvpAttack->getDefenderHealth());
    }

    protected function updateAttackerPvpInfo(Character $attacker, array $healthObject, int $defenderId, int $remainingDefenderHealth = 0) {
        event(new UpdateCharacterPvpAttack($attacker->user, [
            'health_object' => [
                'attacker_max_health' => $healthObject['attacker_health'],
                'attacker_health'     => $this->pvpAttack->getAttackerHealth(),
                'defender_health'     => $healthObject['defender_health'],
                'defender_max_health' => $remainingDefenderHealth,
            ],
            'messages'    => $this->pvpAttack->getMessages()['attacker'],
            'attacker_id' => $defenderId,
        ]));
    }

    protected function updateDefenderPvpInfo(Character $defender, array $healthObject, int $attackerId, int $remainingDefenderHealth = 0) {
        event(new UpdateCharacterPvpAttack($defender->user, [
            'health_object' => [
                'attacker_max_health' => $healthObject['attacker_health'],
                'attacker_health'     => $this->pvpAttack->getAttackerHealth(),
                'defender_health'     => $remainingDefenderHealth,
                'defender_max_health' => $healthObject['defender_health'],
            ],
            'messages'    => $this->pvpAttack->getMessages()['defender'],
            'attacker_id' => $attackerId,
        ]));
    }

    protected function handleDefenderDeath(Character $attacker, Character $defender) {
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
            $item = BuildMythicItem::fetchMythicItem($attacker);

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

    private function movePlayerToNewLocation(Character $character): Character {

        $cache = Cache::get('coordinates');

        $x = $cache['x'];
        $y = $cache['y'];

        $character->map()->update([
            'character_position_x' => $x[rand(0, count($x) - 1)],
            'character_position_y' => $y[rand(0, count($y) - 1)],
        ]);

        $character = $character->refresh();

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

        $character = $character->refresh();

        event(new UpdateMapBroadcast($character->user));

        return $character->refresh();
    }
}
