<?php

namespace App\Game\Core\Services;

use RuntimeException;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\AdventureLog;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\CharacterIsDeadBroadcastEvent;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Events\UpdateAdventureLogsBroadcastEvent;
use App\Game\Core\Exceptions\MonsterIsDeadException;
use App\Game\Core\Exceptions\CharacterIsDeadException;

class AdventureService {

    private $character;

    private $adventure;

    private $levelsAtATime;

    public function __construct(Character $character, Adventure $adventure, $levelsAtATime = 'all') {
        $this->character     = $character;
        $this->adventure     = $adventure;
        $this->levelsAtATime = $levelsAtATime;
    }

    public function processAdventure() {
        if ($this->levelsAtATime === 'all') {
            $this->processAllLevels();
        }
    }

    protected function processAllLevels(): void {
        $attackService = resolve(AdventureFightService::class, [
            'character' => $this->character,
            'adventure' => $this->adventure,
        ]);

        $adventureLog = $this->character->adventureLogs->where('adventure_id', $this->adventure->id)->first();

        $startingLevel = 1;

        if (!is_null($adventureLog->last_completed_level)) {
            $startingLevel = $adventureLog->last_completed_level;
        }

        for ($i = $startingLevel; $i <= $this->adventure->levels; $i++) {
            try {
                $attackService->processBattle();
            } catch (RuntimeException $e) {
                if ($e instanceof CharacterIsDeadException) {
                    $this->character->update([
                        'is_dead'       => true,
                        'can_move'      => true,
                        'can_attack'    => true,
                        'can_craft'     => true,
                        'can_adventure' => true,
                    ]);

                    $this->character->refresh();

                    event(new ServerMessageEvent($this->character->user, 'dead_character'));
                    event(new AttackTimeOutEvent($this->character));
                    event(new CharacterIsDeadBroadcastEvent($this->character->user, true));
                    event(new UpdateTopBarEvent($this->character));

                    $this->updateAdventureLog($adventureLog, $i, true);

                    event(new UpdateAdventureLogsBroadcastEvent($this->character->refresh()->adventureLogs, $this->character->user));

                    dump('character is dead.', $attackService->getLogInformation());

                    return;
                }

                if ($e instanceof MonsterIsDeadException) {
                    $monster = $attackService->getMonster();

                    event(new UpdateCharacterEvent($this->character, $monster, $this->adventure));
                    event(new DropsCheckEvent($this->character, $monster, $this->adventure));
                    event(new GoldRushCheckEvent($this->character, $monster, $this->adventure));

                    if ($this->adventure->levels === $i) {
                        $this->updateAdventureLog($adventureLog, $i);

                        $this->character->update([
                            'can_move'      => true,
                            'can_attack'    => true,
                            'can_craft'     => true,
                            'can_adventure' => true,
                        ]);

                        event(new UpdateAdventureLogsBroadcastEvent($this->character->refresh()->adventureLogs, $this->character->user));
                    }

                    dump('monster is dead.', $attackService->getLogInformation());
                }
            }
        }
    }

    protected function updateAdventureLog(AdventureLog $adventureLog, int $level, bool $isDead = false) {
        if ($isDead) {
            $adventureLog->update([
                'in_progress' => false,
                'last_completed_level' => $level,
            ]);
        } else {
            $adventureLog->update([
                'in_progress' => false,
                'last_completed_level' => $level,
                'complete' => true,
            ]);
        }
    }
}