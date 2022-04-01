<?php

namespace App\Game\Adventures\Services;

use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Messages\Events\GlobalMessageEvent;
use Mail;
use Cache;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\AdventureLog;
use App\Flare\Services\FightService;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Game\Core\Events\CreateAdventureNotificationEvent;
use App\Game\Adventures\Events\UpdateAdventureLogsBroadcastEvent;
use App\Game\Adventures\Builders\RewardBuilder;
use App\Game\Adventures\Mail\AdventureCompleted;


class AdventureService {

    use CanHaveQuestItem;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Adventure $adventure
     */
    private $adventure;

    private $adventureFightService;

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
        'gold'           => 0,
        'exp'            => 0,
        'faction_points' => 0,
        'items'          => [],
    ];

    /**
     * @var array $logInformation
     */
    private $logInformation = [];

    /**
     * Constructor
     *
     * @param Character $character
     * @param Adventure $adventure
     * @param RewardBuilder $rewardbuilder
     * @param string $name
     */
    public function __construct(AdventureFightService $adventureFightService)
    {
        $this->adventureFightService = $adventureFightService;
    }

    public function setCharacter(Character $character): AdventureService {
        $this->character = $character;

        $this->createSkillRewardSection();

        return $this;
    }

    public function setAdventure(Adventure $adventure): AdventureService {
        $this->adventure = $adventure;

        return $this;
    }

    public function setName(string $name): AdventureService {
        $this->name = $name;

        return $this;
    }

    /**
     * Process the adventure.
     *
     * @param int $currentLevel
     * @param int $maxLevel
     * @return void
     */
    public function processAdventure(int $currentLevel, int $maxLevel, string $attackType): void {
        $this->processLevel($currentLevel, $maxLevel, $attackType);
    }

    /**
     * Get the log information.
     *
     * @return array
     */
    public function getLogInformation(): array {
        return $this->logInformation;
    }

    /**
     * Creates the skill reward section if the player is training a skill.
     */
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

    /**
     * Process the level.
     *
     * @param int $currentLevel
     * @param int $maxLevel
     * @param string $attackType
     */
    protected function processLevel(int $currentLevel, int $maxLevel, string $attackType): void {
        $adventureLog = $this->character
                             ->adventureLogs
                             ->where('adventure_id', $this->adventure->id)
                             ->where('in_progress', true)
                             ->first();

        $attackService = $this->adventureFightService->setCharacter($this->character, $attackType)
                                                     ->setAdventure($this->adventure)
                                                     ->setRewards($this->rewards);

        $attackService->processFloor();

        $logs = $attackService->getLogs();

        if (!is_null($adventureLog)) {
            if ($logs['won_fight']) {
                $this->setLogs($logs, $currentLevel, $adventureLog);

                if ($currentLevel >= $maxLevel) {
                    $this->adventureIsOver($adventureLog, $currentLevel, false, false);
                }

            } else if ($logs['took_too_long']) {
                $this->setLogs($logs, $currentLevel, $adventureLog);
                $this->adventureIsOver($adventureLog, $currentLevel, false, true);
            } else {
                $this->characterIsDead($logs, $adventureLog, $currentLevel);
            }
        }
    }

    /**
     * When the character is dead.
     *
     * @param FightService $attackService
     * @param AdventureLog $adventureLog
     * @param int $level
     */
    protected function characterIsDead(array $logs, AdventureLog $adventureLog, int $level) {

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

        $this->setLogs($logs, $level, $adventureLog);

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

    /**
     * Set the log details.
     *
     * @param array $fightLogs
     * @param int $currentLevel
     * @param AdventureLog $adventureLog
     */
    protected function setLogs(array $fightLogs, int $currentLevel, AdventureLog $adventureLog) {

        $logs   = $adventureLog->logs;
        $level = 'Level ' . $currentLevel;

        if (is_null($logs)) {
            $messageDetails         = [];
            $messageDetails[$level] = $fightLogs['messages'];

            $rewardDetails          = [];
            $rewardDetails[$level]  = $fightLogs['reward_info'];

            $adventureLog->update([
                'logs'    => $messageDetails,
                'rewards' => $rewardDetails
            ]);
        } else {

            $rewards = $adventureLog->rewards;

            $logs[$level]    = $fightLogs['messages'];
            $rewards[$level] = $fightLogs['reward_info'];

            $adventureLog->update([
                'logs'    => $logs,
                'rewards' => $rewards,
            ]);
        }
    }

    /**
     * Process that the adventure is over.
     *
     * @param AdventureLog $adventureLog
     * @param int $level
     * @param bool $tookTooLong
     */
    protected function adventureIsOver(AdventureLog $adventureLog, int $level, bool $isDead, bool $tookTooLong) {
        $this->updateAdventureLog($adventureLog, $level, $isDead, $tookTooLong);

        $this->character->update([
            'can_move'               => true,
            'can_attack'             => true,
            'can_craft'              => true,
            'can_adventure'          => true,
            'is_dead'                => false,
            'can_adventure_again_at' => null,
        ]);

        $user      = $this->character->user;

        if (UserOnlineValue::isOnline($user)) {
            event(new UpdateAdventureLogsBroadcastEvent($this->character->adventureLogs, $user));
            event(new ServerMessageEvent($user, 'adventure', 'Adventure completed! Check your logs for more details.'));
        } else if ($user->adventure_email) {
            Mail::to($user->email)->send((new AdventureCompleted($adventureLog->refresh(), $this->character)));
        }

        event(new CreateAdventureNotificationEvent($adventureLog->refresh()));
    }

    /**
     * Update the adventure log.
     *
     * @param AdventureLog $adventureLog
     * @param int $level
     * @param bool $isDead
     */
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
            ]);
        }
    }
}
