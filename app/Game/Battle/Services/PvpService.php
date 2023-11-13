<?php

namespace App\Game\Battle\Services;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Jobs\RemoveKilledInPvpFromUser;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Pvp\PvpAttack;
use App\Game\Battle\Events\UpdateCharacterPvpAttack;
use App\Game\BattleRewardProcessing\Handlers\BattleEventHandler;
use App\Game\Maps\Events\UpdateDuelAtPosition;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Cache;

class PvpService {

    /**
     * @var PvpAttack $pvpAttack
     */
    private PvpAttack $pvpAttack;

    /**
     * @var BattleEventHandler $battleEventHandler
     */
    private BattleEventHandler $battleEventHandler;

    /**
     * @var MapTileValue $mapTileValue
     */
    private MapTileValue $mapTileValue;

    /**
     * @var BuildMythicItem $buildMythicItem
     */
    private BuildMythicItem $buildMythicItem;

    /**
     * @param PvpAttack $pvpAttack
     * @param BattleEventHandler $battleEventHandler
     * @param MapTileValue $mapTileValue
     * @param BuildMythicItem $buildMythicItem
     */
    public function __construct(PvpAttack $pvpAttack, BattleEventHandler $battleEventHandler, MapTileValue $mapTileValue, BuildMythicItem $buildMythicItem) {
        $this->pvpAttack            = $pvpAttack;
        $this->battleEventHandler   = $battleEventHandler;
        $this->mapTileValue         = $mapTileValue;
        $this->buildMythicItem      = $buildMythicItem;
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

        $defenderElementalData = $defender->getInformation()->buildElementalAtonement();
        $attackerElementalData = $attacker->getInformation()->buildElementalAtonement();

        if (!is_null($cache)) {
            return [
                'attacker_health'     => $cache['attacker_health'],
                'attacker_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
                'defender_health'     => $cache['defender_health'],
                'defender_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
                'defender_id'         => $defender->id,
                'attacker_id'         => $attacker->id,
                'defender_atonement'  => !is_null($defenderElementalData) ? $defenderElementalData['highest_element']['name'] : 'N/A',
                'attacker_atonement'  => !is_null($attackerElementalData) ? $attackerElementalData['highest_element']['name'] : 'N/A',
            ];
        }

        return [
            'attacker_health'     => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
            'attacker_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
            'defender_health'     => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
            'defender_max_health' => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
            'defender_id'         => $defender->id,
            'attacker_id'         => $attacker->id,
            'defender_atonement'  => !is_null($defenderElementalData) ? $defenderElementalData['highest_element']['name'] : 'N/A',
            'attacker_atonement'  => !is_null($attackerElementalData) ? $attackerElementalData['highest_element']['name'] : 'N/A',
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

        $this->pvpAttack->attackPlayer($attacker, $defender, $healthObject, $attackType);

        if ($this->pvpAttack->getDefenderHealth() <= 0) {
            $this->processBattleWin($attacker, $defender, $healthObject);

            event(new UpdateDuelAtPosition($defender->user));

            return true;
        }

        if ($this->pvpAttack->getAttackerHealth() <= 0) {
            $this->processBattleWin($defender, $attacker, $healthObject);

            event(new UpdateDuelAtPosition($attacker->user));

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
        event(new ServerMessageEvent($attacker->user, 'You have killed: ' . $defender->name));
        event(new ServerMessageEvent($defender->user, 'You have been killed by: ' . $attacker->name));

        $this->battleEventHandler->processDeadCharacter($defender);

        $this->updateAttackerPvpInfo($attacker, $healthObject, $defender->id);
        $this->updateDefenderPvpInfo($defender, $healthObject, $attacker->id, $this->pvpAttack->getDefenderHealth());

        $this->pvpAttack->cache()->deleteCharacterSheet($attacker);
        $this->pvpAttack->cache()->deleteCharacterSheet($defender);

        $this->pvpAttack->cache()->removeFromPvpCache($attacker);
        $this->pvpAttack->cache()->removeFromPvpCache($defender);

        $this->handleDefenderDeath($defender);
    }

    protected function updateCacheHealthForPVPFight(Character $attacker, Character $defender) {
        $this->pvpAttack->cache()->setPvpData($attacker, $defender, $this->pvpAttack->getAttackerHealth(), $this->pvpAttack->getDefenderHealth());
    }

    protected function updateAttackerPvpInfo(Character $attacker, array $healthObject, int $defenderId, int $remainingDefenderHealth = 0) {
        $attackerElementalAtonement = $attacker->getInformation()->buildElementalAtonement();
        $defenderElementalAtonement = Character::find($defenderId)->getInformation()->buildElementalAtonement();

        event(new UpdateCharacterPvpAttack($attacker->user, [
            'health_object' => [
                'attacker_max_health' => $healthObject['attacker_health'],
                'attacker_health'     => $this->pvpAttack->getAttackerHealth(),
                'defender_health'     => $remainingDefenderHealth,
                'defender_max_health' => $healthObject['defender_health'],
            ],
            'messages'    => $this->pvpAttack->getMessages()['attacker'],
            'attacker_id' => $attacker->id,
            'defender_id' => $defenderId,
            'attacker_atonement' => !is_null($attackerElementalAtonement) ? $attackerElementalAtonement['highest_element']['name'] : 'N/A',
            'defender_atonement' => !is_null($defenderElementalAtonement) ? $defenderElementalAtonement['highest_element']['name'] : 'N/A'
        ]));
    }

    protected function updateDefenderPvpInfo(Character $defender, array $healthObject, int $attackerId, int $remainingDefenderHealth = 0) {
        $defenderElementalAtonement = $defender->getInformation()->buildElementalAtonement();
        $attackerElementalAtonement = Character::find($attackerId)->getInformation()->buildElementalAtonement();

        event(new UpdateCharacterPvpAttack($defender->user, [
            'health_object' => [
                'attacker_max_health' => $healthObject['defender_health'],
                'attacker_health'     => $remainingDefenderHealth,
                'defender_health'     => $this->pvpAttack->getAttackerHealth(),
                'defender_max_health' => $healthObject['attacker_health'],
            ],
            'messages'    => $this->pvpAttack->getMessages()['defender'],
            'attacker_id' => $defender->id,
            'defender_id' => $attackerId,
            'attacker_atonement' => !is_null($attackerElementalAtonement) ? $attackerElementalAtonement['highest_element']['name'] : 'N/A',
            'defender_atonement' => !is_null($defenderElementalAtonement) ? $defenderElementalAtonement['highest_element']['name'] : 'N/A'
        ]));
    }

    protected function handleDefenderDeath(Character $defender) {
        $defender->update([
            'killed_in_pvp' => true,
        ]);

        $defender = $this->movePlayerToNewLocation($defender);

        event(new ServerMessageEvent($defender->user, 'You were safely moved away from your current location. You cannot be targeted by pvp for 2 minutes and, during that time, your location will be masked in chat.'));

        RemoveKilledInPvpFromUser::dispatch($defender)->delay(now()->addMinutes(2));
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

        if (
            !$this->mapTileValue->canWalkOnWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnDeathWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnMagma($character, $character->map->character_position_x, $character->map->character_position_y) ||
            $this->mapTileValue->isPurgatoryWater($this->mapTileValue->getTileColor($character, $character->map->character_position_x, $character->map->character_position_y))
        ) {

            $character->map()->update([
                'character_position_x' => $x[rand(0, count($x) - 1)],
                'character_position_y' => $y[rand(0, count($y) - 1)],
            ]);

            return $this->movePlayerToNewLocation($character->refresh());
        }

        $character = $character->refresh();

        event(new UpdateMap($character->user));

        return $character->refresh();
    }
}
