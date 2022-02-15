<?php

namespace App\Game\Exploration\Services;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Game\Exploration\Jobs\Exploration;
use App\Flare\Values\AutomationType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Skills\Services\SkillService;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Events\UpdateAutomationsList;

class ExplorationAutomationService {

    use ResponseBuilder;

    /**
     * @var SkillService $skillService
     */
    private $skillService;

    /**
     * @param SkillService $skillService
     */
    public function __construct(SkillService $skillService) {
        $this->skillService = $skillService;
    }

    /**
     * @param Character $character
     * @param array $params
     */
    public function beginAutomation(Character $character, array $params): array {

        if (!is_null($params['skill_id']) && !is_null($params['xp_towards'])) {
            $result = $this->switchSkills($character, $params['skill_id'], $params['xp_towards']);

            if (!$result instanceof Character) {
                // @codeCoverageIgnoreStart
                return $result;
                // @codeCoverageIgnoreEnd
            }

            $character = $result;
        }

        $automation = CharacterAutomation::create([
            'character_id'                  => $character->id,
            'monster_id'                    => $params['selected_monster_id'],
            'type'                          => AutomationType::EXPLORING,
            'started_at'                    => now(),
            'completed_at'                  => now()->addMinutes(12), // now()->addHours($params['auto_attack_length']),
            'move_down_monster_list_every'  => $params['move_down_the_list_every'],
            'previous_level'                => $character->level,
            'current_level'                 => $character->level,
            'attack_type'                   => $params['attack_type'],
        ]);

        $delay = $automation->completed_at->diffInSeconds(now());

        $character = $character->refresh();

        event(new ExplorationTimeOut($character->user, $delay));
        event (new ExplorationStatus($character->user, true));
        event(new UpdateTopBarEvent($character));
        event(new UpdateAutomationsList($character->user, $character->currentAutomations));

        Exploration::dispatch($character, $automation->id, $automation->attack_type)->onConnection('long_running')->delay(now()->addMinutes(10));

        event(new ExplorationLogUpdate($character->user, 'First round will begin in 10 minutes.'));

        return $this->successResult([
            'message' => 'The exploration is underway. Soon you will set out for the grandest adventure of your life. Keep an eye on the Exploration
            tab to get information about how the exploration is going. Check the help tab for more info or the Help docs for further details.'
        ]);
    }

    public function fetchData(Character $character, ?CharacterAutomation $automation = null): array {
        $skillCurrentlyTraining = $character->skills->filter(function ($skill) {
            return $skill->currently_training;
        })->first();

        $data = [];

        if (!is_null($automation)) {
            $data = [
                'id'                       => $automation->id,
                'skill_id'                 => !is_null($skillCurrentlyTraining) ? $skillCurrentlyTraining->id : null,
                'xp_towards'               => !is_null($skillCurrentlyTraining) ? $skillCurrentlyTraining->xp_towards : null,
                'auto_attack_length'       => $automation->completed_at->diffInSeconds(now()),
                'move_down_the_list_every' => $automation->move_down_monster_list_every,
                'selected_monster_id'      => $automation->monster_id,
                'attack_type'              => $automation->attack_type,
            ];

            event(new ExplorationStatus($character->user, true));
        } else {
            event(new ExplorationStatus($character->user, false));
        }

        return $data;
    }

    protected function switchSkills(Character $character, int $skillId, float $xp): Character|array {
        $result = $this->skillService->trainSkill($character, $skillId, $xp);

        if ($result['status'] !== 200) {
            // @codeCoverageIgnoreStart
            return $result;
            // @codeCoverageIgnoreEnd
        }

        return $character->refresh();
    }
}
