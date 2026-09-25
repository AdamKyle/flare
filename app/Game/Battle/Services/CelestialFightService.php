<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Map;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Events\UpdateCelestialFight;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Jobs\CelestialTimeOut;
use App\Game\Battle\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\BattleRewardProcessing\Jobs\BattleAttackHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterCelestialTimeOut;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class CelestialFightService
{
    use ChecksAutomationRestrictions, ResponseBuilder;

    /**
     * @param BattleEventHandler $battleEventHandler
     * @param CharacterCacheData $characterCacheData
     * @param MonsterPlayerFight $monsterPlayerFight
     * @param MapTileValue $mapTileValue
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param CoordinatesQuery $coordinatesQuery
     */
    public function __construct(
        private readonly BattleEventHandler $battleEventHandler,
        private readonly CharacterCacheData $characterCacheData,
        private ?MonsterPlayerFight $monsterPlayerFight,
        private readonly MapTileValue $mapTileValue,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly CoordinatesQuery $coordinatesQuery,
    ) {}

    /**
     * Join the Character to the Celestial fight, creating or refreshing their cached fight health.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return CharacterInCelestialFight
     */
    public function joinFight(Character $character, CelestialFight $celestialFight): CharacterInCelestialFight
    {
        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();

        $health = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if (is_null($characterInCelestialFight)) {
            $characterInCelestialFight = CharacterInCelestialFight::create([
                'celestial_fight_id' => $celestialFight->id,
                'character_id' => $character->id,
                'character_max_health' => $health,
                'character_current_health' => $health,
            ]);
        } else {
            if (now()->diffInMinutes($characterInCelestialFight->updated_at) > 5) {
                $characterInCelestialFight = $this->updateCharacterInFight($character, $characterInCelestialFight);
            }

            if ($health !== $characterInCelestialFight->character_current_health) {
                $characterInCelestialFight = $this->updateCharacterInFight($character, $characterInCelestialFight);
            }
        }

        return $characterInCelestialFight;
    }

    /**
     * Resolve one Character attack against the Celestial fight and return the updated fight state.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @param CharacterInCelestialFight $characterInCelestialFight
     * @param string $attackType
     * @return array
     */
    public function fight(Character $character, CelestialFight $celestialFight, CharacterInCelestialFight $characterInCelestialFight, string $attackType): array
    {
        $restriction = $this->automationRestrictionErrorResult($character, AutomationRestrictionService::CELESTIAL_FIGHTING);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if (! $this->isPlayerAtSameLocationAsCelestialFight($character->map, $celestialFight)) {
            return $this->errorResult('You are not at the same location as the celestial.
            Use /pc to see the location or /pct if you have the quest item to be auto transported to the celestial.');
        }

        $result = $this->monsterPlayerFight->setUpFight($character, [
            'attack_type' => $attackType,
            'selected_monster_id' => $celestialFight->monster_id,
            'current_monster_health' => $celestialFight->current_health,
            'max_monster_health' => $celestialFight->max_health,
        ])->fightMonster(true);

        if ($result) {
            $messages = $this->monsterPlayerFight->getBattleMessages();

            $this->monsterPlayerFight = null;

            $characterHealth = $characterInCelestialFight->character_max_health;

            $this->handleMonsterDeath($character, $celestialFight);

            return $this->successResult([
                'logs' => $messages,
                'health' => [
                    'current_character_health' => $characterHealth,
                    'current_monster_health' => 0,
                ],
            ]);
        }

        $characterHealth = max($this->monsterPlayerFight->getCharacterHealth(), 0);
        $monsterHealth = min(
            max($this->monsterPlayerFight->getMonsterHealth(), 0),
            $celestialFight->max_health
        );

        if ($characterHealth <= 0) {
            $celestialFight = $this->moveCelestial($character, $celestialFight);
            $monsterHealth = $celestialFight->current_health;

            $this->battleEventHandler->processDeadCharacter($character);

            event(new UpdateCelestialFight($this->monsterPlayerFight, $monsterHealth, $celestialFight->id));
        }

        $characterInCelestialFight->update([
            'character_current_health' => $characterHealth,
        ]);

        if ($characterHealth > 0 && $monsterHealth > 0) {
            $celestialFight = $this->moveCelestial($character, $celestialFight);
            $monsterHealth = $celestialFight->current_health;

            event(new UpdateCelestialFight($this->monsterPlayerFight, $monsterHealth, $celestialFight->id));
        }

        return $this->successResult([
            'logs' => $this->monsterPlayerFight->getBattleMessages(),
            'health' => [
                'current_character_health' => $characterHealth,
                'current_monster_health' => $monsterHealth,
            ],
        ]);
    }

    /**
     * Restore the Character's cached Celestial fight health and return the current fight state.
     *
     * @param Character $character
     * @return array
     */
    public function revive(Character $character): array
    {
        $character = $this->battleEventHandler->processRevive($character);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $celestialFight = CelestialFight::find($characterInCelestialFight->celestial_fight_id);

        return $this->successResult([
            'fight' => [
                'character' => [
                    'max_health' => $characterInCelestialFight->character_max_health,
                    'current_health' => $characterInCelestialFight->character_current_health,
                ],
                'monster' => [
                    'max_health' => $celestialFight->max_health,
                    'current_health' => $celestialFight->current_health,
                ],
            ],
        ]);
    }

    /**
     * Determine whether the Character's current map position matches the Celestial fight's location.
     *
     * @param Map $map
     * @param CelestialFight $celestialFight
     * @return bool
     */
    private function isPlayerAtSameLocationAsCelestialFight(Map $map, CelestialFight $celestialFight): bool
    {
        $characterX = $map->character_position_x;
        $characterY = $map->character_position_y;

        return $celestialFight->x_position === $characterX &&
            $celestialFight->y_position === $characterY &&
            $celestialFight->monster->game_map_id === $map->game_map_id;
    }

    /**
     * Handle the Celestial's death: apply the engagement timeout, grant shards, and dispatch the reward handler.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return void
     */
    private function handleMonsterDeath(Character $character, CelestialFight $celestialFight): void
    {
        event(new UpdateCelestialFight(null, 0, $celestialFight->id));

        $character = $this->timeOutCelestialEvent($character);

        $this->giveShards($character, $celestialFight);

        BattleAttackHandler::dispatch($character->id, $celestialFight->monster_id)->onQueue('battle_reward_processing')->onConnection('battle_reward_processing');

        $celestialFightType = new CelestialConjureType($celestialFight->type);

        if ($celestialFightType->isPublic()) {
            event(new GlobalMessageEvent($character->name.' has slain the '.$celestialFight->monster->name.'! They have been rewarded with a godly gift!'));
        } else {
            event(new ServerMessageEvent($character->user, 'You have slain the '.$celestialFight->monster->name.'! They have been rewarded with a godly gift!'));
        }

        $this->characterCacheData->deleteCharacterSheet($character);

        CharacterInCelestialFight::where('celestial_fight_id', $celestialFight->id)->delete();

        $celestialFight->delete();
    }

    /**
     * Apply the Character's 10-second Celestial re-engagement timeout and broadcast the current status.
     *
     * @param Character $character
     * @return Character
     */
    private function timeOutCelestialEvent(Character $character): Character
    {
        $timeLeft = now()->addSeconds(10);

        $character->update([
            'can_engage_celestials' => false,
            'can_engage_celestials_again_at' => $timeLeft,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        broadcast(new UpdateCharacterCelestialTimeOut($character->user, 10));

        CelestialTimeOut::dispatch($character)->delay(10);

        return $character->refresh();
    }

    /**
     * Grant the Character the defeated Celestial's shards, capped at the max shards limit.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return void
     */
    private function giveShards(Character $character, CelestialFight $celestialFight): void
    {
        $monsterShards = $celestialFight->monster->shards;

        $shards = $character->shards + $monsterShards;

        if ($shards >= CurrencyLimit::MAX_SHARDS) {
            $shards = CurrencyLimit::MAX_SHARDS;
        }

        $character->update([
            'shards' => $shards,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        event(new ServerMessageEvent($character->user, 'You received: '.number_format($monsterShards).' shards! Shards can only be used in Alchemy.'));
    }

    /**
     * Refresh the Character's cached health onto their Celestial fight participation record.
     *
     * @param Character $character
     * @param CharacterInCelestialFight $characterInCelestialFight
     * @return CharacterInCelestialFight
     */
    private function updateCharacterInFight(Character $character, CharacterInCelestialFight $characterInCelestialFight): CharacterInCelestialFight
    {
        $health = $this->characterCacheData->getCachedCharacterData($character, 'health');

        $characterInCelestialFight->update([
            'character_max_health' => $health,
            'character_current_health' => $health,
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        return $characterInCelestialFight->refresh();
    }

    /**
     * Move the Celestial to a new valid location and announce its flight.
     *
     * @param Character $character
     * @param CelestialFight $celestialFight
     * @return CelestialFight
     */
    private function moveCelestial(Character $character, CelestialFight $celestialFight): CelestialFight
    {
        $monster = $celestialFight->monster;

        $celestialFight->update(array_merge([
            'current_health' => $celestialFight->max_health,
        ], $this->getCelestialCoordinates($celestialFight)));

        $celestialFight = $celestialFight->refresh();

        $celestialFightType = new CelestialConjureType($celestialFight->type);

        if ($celestialFightType->isPublic()) {
            event(new GlobalMessageEvent($character->name.' Has caused: '.$monster->name.' to flee to the far ends of Tlessa (use /pct or /pc to find the new coordinates).'));
        } else {
            event(new ServerMessageEvent($character->user, 'You Have caused: '.$monster->name.' to flee to the far ends of Tlessa (use /pct or /pc to find the new coordinates).'));
        }

        return $celestialFight;
    }

    /**
     * Resolve valid Celestial coordinates, re-rolling water tiles on special maps.
     *
     * @param CelestialFight $celestialFight
     * @return array
     */
    private function getCelestialCoordinates(CelestialFight $celestialFight): array
    {
        $coordinates = $this->coordinatesQuery->get();
        $xPosition = $coordinates->x[$this->randomNumberGenerator->numberBetween($coordinates->x[0], count($coordinates->x) - 1)];
        $yPosition = $coordinates->y[$this->randomNumberGenerator->numberBetween($coordinates->y[0], count($coordinates->y) - 1)];
        $gameMap = $celestialFight->monster->gameMap;

        if ($gameMap->mapType()->isTwistedMemories() || $gameMap->mapType()->isDelusionalMemories()) {
            $isTwistedMemoriesWater = $this->mapTileValue->isTwistedMemoriesWater(
                $this->mapTileValue->getTileColor($xPosition, $yPosition)
            );

            $isDelusionalMemoriesWater = $this->mapTileValue->isDelusionalMemoriesWater(
                $this->mapTileValue->getTileColor($xPosition, $yPosition)
            );

            if ($isTwistedMemoriesWater || $isDelusionalMemoriesWater) {
                return $this->getCelestialCoordinates($celestialFight);
            }
        }

        return [
            'x_position' => $xPosition,
            'y_position' => $yPosition,
        ];
    }
}
