<?php

namespace App\Game\Maps\Adventure\Services;

use Mail;
use Cache;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\AdventureLog; 
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Game\Core\Events\CreateAdventureNotificationEvent;
use App\Game\Maps\Adventure\Events\UpdateAdventureLogsBroadcastEvent;
use App\Game\Maps\Adventure\Builders\RewardBuilder;
use App\Game\Maps\Adventure\Mail\AdventureCompleted;


class AdventureService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Adventure $adventure
     */
    private $adventure;

    /**
     * @var RewardBuilder $rewardBuilder
     */
    private $rewardBuilder;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var array $rewards
     */
    private $rewards = [
        'gold'  => 0,
        'exp'   => 0,
        'items' => [],
    ];

    /**
     * Constructor
     * 
     * @param Character $character
     * @param Adventure $adventure
     * @param RewardBuilder $rewardbuilder
     * @param string $name
     */
    public function __construct(
        Character $character, 
        Adventure $adventure, 
        RewardBuilder $rewardBuilder,
        string $name) 
    {
        $this->character          = $character;
        $this->adventure          = $adventure;
        $this->rewardBuilder      = $rewardBuilder; 
        $this->name               = $name;

        $this->createSkillRewardSection();
    }

    /**
     * Process the adventure.
     * 
     * @param int $currentLevel
     * @param int $maxLevel
     * @return void
     */
    public function processAdventure(int $currentLevel, int $maxLevel): void {
        $this->processLevel($currentLevel, $maxLevel);
    }

    protected function createSkillRewardSection(): void {
        $skill = $this->character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();

        if (!is_null($skill)) {
            $this->rewards['skill'] = [
                'skill_name' => $skill->name, 
                'exp_towards' => $skill->xp_towards,
                'exp'   => 0,
            ];
        }
    }

    protected function processLevel(int $currentLevel, int $maxLevel): void {
        $attackService = resolve(AdventureFightService::class, [
            'character' => $this->character,
            'adventure' => $this->adventure,
        ]);

        $adventureLog = $this->character
                             ->adventureLogs
                             ->where('adventure_id', $this->adventure->id)
                             ->where('in_progress', true)
                             ->first();

        $attackService->processBattle();

        if ($attackService->isCharacterDead()) {
            $this->characterIsDead($attackService, $adventureLog, $currentLevel);

            return;
        }

        if ($attackService->isMonsterDead()) {
            $this->monsterIsDead($attackService, $adventureLog);

            if ($this->adventure->levels === $currentLevel) {
                $this->adventureIsOver($adventureLog, $currentLevel);

                return;
            }
        }

        if ($currentLevel === $maxLevel && $attackService->tooLong()) {
            $this->adventureTookToLong($attackService, $adventureLog);

            $this->adventureIsOver($adventureLog, $currentLevel, true);

            return;
        }

        $attackService->resetLogInfo();

        return;
    }

    protected function adventureTookToLong(AdventureFightService $attackService, AdventureLog $adventureLog) {
        Cache::forget('character_'.$this->character->id.'_adventure_'.$this->adventure->id);

        $this->character->update([
            'can_move'               => true,
            'can_attack'             => true,
            'can_craft'              => true,
            'can_adventure'          => true,
            'can_adventure_again_at' => null,
        ]);

        $this->setLogs($adventureLog, $attackService);

        $this->character->refresh();

        $character = $this->character->refresh();

        if (UserOnlineValue::isOnline($character->user)) {
            event(new UpdateAdventureLogsBroadcastEvent($character->refresh()->adventureLogs, $character->user));
            event(new ServerMessageEvent($character->user, 'adventure', 'The adventure took too long per floor. Check the logs for more info.'));
        } else {
            Mail::to($this->character->user->email)->send(new AdventureCompleted($adventureLog->refresh(), $character));
        }
    }

    protected function characterIsDead(AdventureFightService $attackService, AdventureLog $adventureLog, int $level) {
        Cache::forget('character_'.$this->character->id.'_adventure_'.$this->adventure->id);

        $this->character->update([
            'can_move'               => true,
            'can_attack'             => true,
            'can_craft'              => true,
            'can_adventure'          => true,
            'is_dead'                => true,
            'can_adventure_again_at' => null,
        ]);

        event(new AttackTimeOutEvent($this->character));

        $this->setLogs($adventureLog, $attackService);

        $this->updateAdventureLog($adventureLog, $level, true);

        $character = $this->character->refresh();
        
        if (UserOnlineValue::isOnline($character->user)) {
            event(new ServerMessageEvent($character->user, 'dead_character'));
            event(new CharacterIsDeadBroadcastEvent($character->user, true));
            event(new UpdateTopBarEvent($character));
            event(new UpdateAdventureLogsBroadcastEvent($character->refresh()->adventureLogs, $character->user));
            event(new ServerMessageEvent($character->user, 'adventure', 'You died while on your explortations! Check your Adventure logs for more information.'));
        } else {
            Mail::to($this->character->user->email)->send(new AdventureCompleted($adventureLog->refresh(), $character));
        }

       
        event(new CreateAdventureNotificationEvent($adventureLog->refresh()));
    } 

    protected function monsterIsDead(AdventureFightService $attackService, AdventureLog $adventureLog) {
        $monster     = $attackService->getMonster();

        $xpReduction = 0.0;

        if (isset($this->rewards['skill'])) {
            $xpReduction = $this->rewards['skill']['exp_towards'];

            $foundSkill = $this->character->skills()->join('game_skills', function($join) {
                $join->on('game_skills.id', 'skills.game_skill_id')
                     ->where('game_skills.name', $this->rewards['skill']['skill_name']);
            })->first();

            $this->rewards['skill']['exp'] += $this->rewardBuilder->fetchSkillXPReward($foundSkill, $this->adventure);
        }

        $this->rewards['exp'] += $this->rewardBuilder->fetchXPReward($monster, $this->character->level, $xpReduction);

        $drop = $this->rewardBuilder->fetchDrops($monster, $this->character, $this->adventure);
        
        if (!is_null($drop)) {
            $this->rewards['items'][] = [
                'id' => $drop->id,
                'name' => $drop->affix_name,
            ];
        }

        $this->rewards['gold'] += $this->rewardBuilder->fetchGoldRush($monster, $this->character, $this->adventure);

        $this->setLogs($adventureLog, $attackService);
    }

    protected function setLogs(AdventureLog $adventureLog, AdventureFightService $attackService) {
        $logs = $adventureLog->logs;

        if (is_null($logs)) {

            $logDetails              = [];
            $logDetails[$this->name] = [$attackService->getLogInformation()];

            $adventureLog->update([
                'logs' => $logDetails,
            ]);
        } else {
            $logs[$this->name][] = $attackService->getLogInformation();
            
            $adventureLog->update([
                'logs' => $logs,
            ]);
        }
    }

    protected function adventureIsOver(AdventureLog $adventureLog, int $level, bool $tookTooLong = false) {
        $this->updateAdventureLog($adventureLog, $level, false, $tookTooLong);

        $this->character->update([
            'can_move'               => true,
            'can_attack'             => true,
            'can_craft'              => true,
            'can_adventure'          => true,
            'is_dead'                => false,
            'can_adventure_again_at' => null,
        ]);

        $rewardItemId = $adventureLog->adventure->reward_item_id;

        if (!is_null($rewardItemId)) {
            $foundItem    = $this->character->inventory->slots->filter(function($slot) use ($rewardItemId) {
                return $slot->item_id === $rewardItemId;
            })->first();
    
            if (is_null($foundItem)) {
                $this->character->inventory->slots()->create([
                    'inventory_id' => $this->character->inventory->id,
                    'item_id'      => $rewardItemId,
                ]);
    
                event(new ServerMessageEvent($this->character->user, 'found_item', $adventureLog->adventure->itemReward->name));
            }
        }

        $character = $this->character->refresh();

        if (UserOnlineValue::isOnline($this->character->user)) {
            event(new UpdateAdventureLogsBroadcastEvent($character->adventureLogs, $character->user));
            event(new ServerMessageEvent($this->character->user, 'adventure', 'Adventure completed! Check your logs for more details.'));
        } else {
            Mail::to($this->character->user->email)->send(new AdventureCompleted($adventureLog->refresh(), $character));
        }

        event(new CreateAdventureNotificationEvent($adventureLog->refresh()));
    } 

    protected function updateAdventureLog(AdventureLog $adventureLog, int $level, bool $isDead = false, bool $tookTooLong = false) {
        if ($isDead) {
            $adventureLog->update([
                'in_progress'          => false,
                'last_completed_level' => $level,
                'rewards'              => null,
            ]);
        } else if ($tookTooLong) {
            $adventureLog->update([
                'in_progress'          => false,
                'last_completed_level' => $level,
                'rewards'              => null,
                'took_to_long'         => true,
            ]);
        } else {
            $adventureLog->update([
                'in_progress'          => false,
                'last_completed_level' => $level,
                'complete'             => true,
                'rewards'              => $this->rewards,
            ]);
        }
    }
}