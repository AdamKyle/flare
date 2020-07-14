<?php

namespace App\Game\Core\Services;

use RuntimeException;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\AdventureLog;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
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
                    
                    $this->characterIsDead($attackService, $adventureLog, $i);

                    return;
                }

                if ($e instanceof MonsterIsDeadException) {
                    $this->monsterIsDead($attackService, $adventureLog);

                    if ($this->adventure->levels === $i) {
                        $this->adventureIsOver($adventureLog, $i);

                        return;
                    }
                }
            }
        }
    }

    protected function characterIsDead(AdventureFightService $attackService, AdventureLog $adventureLog, int $level) {
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

        $this->updateAdventureLog($adventureLog, $level, true);

        event(new UpdateAdventureLogsBroadcastEvent($this->character->refresh()->adventureLogs, $this->character->user));

        event(new ServerMessageEvent($this->character->user, 'adventure', 'You died while on your explortations! Chek your Adventure logs for more information. Any rewards you gained before desth is below.'));

        $this->setLogs($adventureLog, $attackService);
    } 

    protected function monsterIsDead(AdventureFightService $attackService, AdventureLog $adventureLog) {
        $monster = $attackService->getMonster();

        event(new UpdateCharacterEvent($this->character, $monster, $this->adventure));
        event(new DropsCheckEvent($this->character, $monster, $this->adventure));
        event(new GoldRushCheckEvent($this->character, $monster, $this->adventure));

        $this->setLogs($adventureLog, $attackService);
    }

    protected function setLogs(AdventureLog $adventureLog, AdventureFightService $attackService) {
        $logs = json_decode($adventureLog->logs);

        if (empty($logs)) {
            $adventureLog->update([
                'logs' => json_encode($attackService->getLogInformation()),
            ]);
        } else {
            $logs[] = $attackService->getLogInformation();

            $adventureLog->update([
                'logs' => json_encode($logs),
            ]);
        }
    }

    protected function adventureIsOver(AdventureLog $adventureLog, int $level) {
        $this->updateAdventureLog($adventureLog, $level);

        $this->character->update([
            'can_move'               => true,
            'can_attack'             => true,
            'can_craft'              => true,
            'can_adventure'          => true,
            'can_adventure_again_at' => null,
        ]);

        $rewardItemId = $adventureLog->adventure->reward_item_id;

        $foundItem    = $this->character->inventory->questItemSlots->filter(function($slot) use ($rewardItemId) {
            return $slot->item_id === $rewardItemId;
        })->first();

        if (is_null($foundItem)) {
            $this->character->inventory->questItemSlots()->create([
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $rewardItemId,
            ]);

            event(new ServerMessageEvent($this->character->user, 'found_item', $adventureLog->adventure->itemReward->name));
        }

        $character = $this->character->refresh();

        event(new UpdateAdventureLogsBroadcastEvent($character->adventureLogs, $character->user));

        event(new ServerMessageEvent($this->character->user, 'adventure', 'Adventure completed! Check your logs for more details. Below is your rewards.'));
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