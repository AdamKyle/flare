<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Services\FightService;
use App\Game\Battle\Events\UpdateCelestialFight;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Events\AttackTimeOutEvent;
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
                $characterInCelestialFight = $this->updateCharacterInFight($character, $characterInCelestialFight);
            }

            if ($character->getInformation()->buildHealth() !== $characterInCelestialFight->character_current_health) {
                $characterInCelestialFight = $this->updateCharacterInFight($character, $characterInCelestialFight);
            }
        }

        return $characterInCelestialFight;
    }

    public function fight(Character $character, CelestialFight $celestialFight, CharacterInCelestialFight $characterInCelestialFight): array {
        $fightService = resolve(FightService::class, [
            'character' => $character,
            'monster'   => $celestialFight->monster,
        ])->overrideMonsterHealth($celestialFight->current_health)
          ->overrideCharacterHealth($characterInCelestialFight->character_current_health)
          ->setAttackTimes(1);

        $fightService->attack($character, $celestialFight->monster);

        $celestialFight->update([
            'current_health' => $fightService->getRemainingMonsterHealth()
        ]);

        $characterInCelestialFight->update([
            'character_current_health' => $fightService->getRemainingCharacterHealth()
        ]);

        $logInfo = $fightService->getLogInformation();

        if ($fightService->isCharacterDead()) {
            $this->battleEventHandler->processDeadCharacter($character);

            $characterInCelestialFight = $characterInCelestialFight->refresh();
            $celestialFight            = $celestialFight->refresh();

            event(new UpdateCelestialFight($celestialFight, false));

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
                'shards' => $character->shards + $celestialFight->monster->shards,
            ]);

            $this->battleEventHandler->processMonsterDeath($character->refresh(), $celestialFight->monster_id);

            event(new GlobalMessageEvent($character->name . ' has slain the '.$celestialFight->monster->name.'! They have been rewarded with a godly gift!'));

            event(new ServerMessageEvent($character->user, 'You received: ' . $celestialFight->monster->shards . ' shards! Shards can only be used in Alchemy.'));

            CharacterInCelestialFight::where('celestial_fight_id', $celestialFight->id)->delete();

            $celestialFight->delete();

            event(new UpdateCelestialFight(null, true));

            return $this->successResult([
                'battle_over' => true,
                'logs'        => $logInfo[0]['messages'],
            ]);
        }

        event(new AttackTimeOutEvent($character));

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

    public function revive(Character $character) {
        $character = $this->battleEventHandler->processRevive($character);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $celestialFight            = CelestialFight::find($characterInCelestialFight->celestial_fight_id);

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

    protected function updateCharacterInFight(Character $character, CharacterInCelestialFight $characterInCelestialFight) {
        $characterInCelestialFight->update([
            'character_max_health'    => $character->getInformation()->buildHealth(),
            'character_current_health'=> $character->getInformation()->buildHealth(),
        ]);

        return $characterInCelestialFight->refresh();
    }
}
