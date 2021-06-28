<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Services\FightService;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class CelestialFightService {

    use ResponseBuilder;

    private $battleEventHandler;

    public function __construct(BattleEventHandler $battleEventHandler) {
        $this->battleEventHandler = $battleEventHandler;
    }

    public function joinFight(Character $character, CelestialFight $celestialFight): CharacterInCelestialFight {
        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();

        if (is_null($characterInCelestialFight)) {
            $characterInCelestialFight = CharacterInCelestialFight::create([
                'celestial_fight_id'      => $celestialFight->id,
                'character_id'            => $character->id,
                'character_max_health'    => $character->getInformation()->buildHealth(),
                'character_current_health'=> $character->getInformation()->buildHealth(),
            ]);
        } else {
            if (now()->diffInMinutes($characterInCelestialFight->updated_at) > 5) {
                $characterInCelestialFight->update([
                    'character_max_health'    => $character->getInformation()->buildHealth(),
                    'character_current_health'=> $character->getInformation()->buildHealth(),
                ]);

                $characterInCelestialFight = $characterInCelestialFight->refresh();
            }
        }

        return $characterInCelestialFight;
    }

    public function fight(Character $character, CelestialFight $celestialFight, CharacterInCelestialFight $characterInCelestialFight): array {
        $fightService = resolve(FightService::class, [
            'character'            => $character,
            'monster'              => $celestialFight->monster,
            'monsterCurrentHealth' => $celestialFight->current_health,
            'characterHealth'      => $characterInCelestialFight->character_current_health
        ]);

        $fightService->attack($character, $celestialFight->monster);

        $celestialFight->update([
            'current_health' => $fightService->getRemainingMonsterHealth()
        ]);

        $characterInCelestialFight->update([
            'character_current_health' => $fightService->getRemainingCharacterHealth()
        ]);

        $logInfo                   = $fightService->getLogInformation();
        dump($logInfo);
        if ($fightService->isCharacterDead()) {
            $this->battleEventHandler->processDeadCharacter($character);

            $characterInCelestialFight = $characterInCelestialFight->refresh();
            $celestialFight            = $celestialFight->refresh();

            return $this->successResult([
                'fight' => [
                    'character' =>[
                        'max_health'     => $characterInCelestialFight->character_max_health,
                        'current_health' => $characterInCelestialFight->character_current_health,
                    ],
                    'monster' => [
                        'max_health'     => $celestialFight->max_health,
                        'current_health' => $celestialFight->current_health,
                    ]
                ],
                'logs' => array_merge($logInfo[0]['messages'], $logInfo[1]['messages']),
            ]);
        }

        if ($fightService->isMonsterDead()) {
            $character->update([
                'shards' => $celestialFight->monster->shards,
            ]);

            $this->battleEventHandler->processMonsterDeath($character->refresh(), $celestialFight->monster_id);

            event(new GlobalMessageEvent($character->name . ' has slain the '.$celestialFight->monster->name.'! They have been rewarded with a godly gift!'));

            event(new ServerMessageEvent($character->user, 'You received: ' . $celestialFight->monster->shards));

            return $this->successResult([
                'battle_over' => true,
                'logs'        => array_merge($logInfo[0]['messages'], $logInfo[1]['messages']),
            ]);
        }

        dump($fightService->getLogInformation(), $fightService->getRemainingMonsterHealth(), $fightService->getRemainingCharacterHealth(), $fightService->isMonsterDead(), $fightService->isCharacterDead());
    }

    public function revive(Character $character) {
        $character = $this->battleEventHandler->processRevive($character);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $celestialFight            = CelestialFight::find($characterInCelestialFight->celestial_fight_id);

        if (!is_null($characterInCelestialFight)) {
            $characterInCelestialFight->update([
                'character_current_health' => $character->getInformation()->buildHealth(),
            ]);

            return $this->successResult([
                'fight' => [
                    'character' =>[
                        'max_health'     => $characterInCelestialFight->character_max_health,
                        'current_health' => $characterInCelestialFight->character_current_health,
                    ],
                    'monster' => [
                        'max_health'     => $celestialFight->max_health,
                        'current_health' => $celestialFight->current_health,
                    ]
                ],
            ]);
        }
    }
}
