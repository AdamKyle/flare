<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Game\Automation\Events\AutomationAttackTimeOut;
use App\Game\Automation\Jobs\AttackAutomation;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\SkillService;

class AttackAutomationService {

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
        $result = $this->switchSkills($character, $params['skill_id'], $params['xp_towards']);

        if (!$result instanceof Character) {
            return $result;
        }

        $character = $result;

        $automation = CharacterAutomation::create([
            'character_id'                  => $character->id,
            'monster_id'                    => $params['selected_monster_id'],
            'type'                          => AutomationType::ATTACK,
            'started_at'                    => now(),
            'move_down_monster_list_every'  => $params['move_down_the_list_every'],
            'previous_level'                => $character->level,
            'current_level'                 => $character->level,
        ]);

        $delay = now()->addSeconds(30);

        event(new AutomationAttackTimeOut($character->user, 30));

        AttackAutomation::dispatch($character, $automation->id, $params['attack_type'])->delay($delay);

        return $this->successResult([
            'message' => 'Automation has begun! You will not be able to fight celestials, teleport, set sail, manage your equipped items or training skills.
            You can of course move by walking, manage your kingdoms and craft. You cannot purchase from the shop or visit the market board or do adventures.
            Fights will begin in 30 seconds!'
        ]);
    }

    protected function switchSkills(Character $character, int $skillId, float $xp): Character|array {
        $result = $this->skillService->trainSkill($character, $skillId, $xp);

        if ($result['status'] !== 200) {
            return $result;
        }

        return $character->refresh();
    }
}