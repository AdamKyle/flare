<?php

namespace App\Game\Skills\Listeners;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\Skill;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Events\Values\EventType;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Flare\Calculators\SkillXPCalculator;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class UpdateSkillListener
{
    private SkillService $skillService;

    public function __construct(SkillService $skillService)
    {
        $this->skillService = $skillService;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(UpdateSkillEvent $event)
    {
        if ($event->skill->level >= $event->skill->baseSkill->max_level) {
            return;
        }

        $skillXP = $this->getSkillXp($event->skill, $event->monster);

        $this->updateSkill($event->skill, $skillXP);

        if ($event->skill->type()->isDisenchanting()) {
            $enchantingSkill = Skill::where('game_skill_id', GameSkill::where('type', SkillTypeValue::ENCHANTING)->first()->id)
                ->where('character_id', $event->skill->character_id)
                ->first();

            $xp = ceil($this->getSkillXp($enchantingSkill) / 2);

            if ($enchantingSkill->level < $enchantingSkill->baseSkill->max_level) {
                $this->updateSkill($enchantingSkill, $xp);
            }
        }
    }

    protected function getSkillXp(Skill $skill, ?Monster $monster = null): float|int
    {
        $gameMap = $skill->character->map->gameMap;

        $skillXP = SkillXPCalculator::fetchSkillXP($skill, $monster);

        if (! is_null($gameMap->skill_training_bonus)) {
            $skillXP = $skillXP + $skillXP * $gameMap->skill_training_bonus;
        }

        return $skillXP;
    }

    protected function updateSkill(Skill $skill, int $skillXP)
    {
        $newXp = $skill->xp + $skillXP;

        $event = ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)->where('currently_running', true)->first();

        if (!is_null($event)) {
            $newXp += 150;
        }

        while ($newXp >= $skill->xp_max) {
            $level = $skill->level + 1;

            $bonus = $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level;

            if ($skill->baseSkill->max_level === $level) {
                $bonus = 1.0;
            }

            $newXp -= $skill->xp_max;

            $skill->update([
                'level' => $level,
                'xp_max' => $skill->can_train ? $level * 10 : rand(100, 350),
                'base_damage_mod' => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                'base_healing_mod' => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                'base_ac_mod' => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                'move_time_out_mod' => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                'skill_bonus' => $bonus,
                'xp' => 0,
            ]);

            $character = $skill->character->refresh();

            event(new SkillLeveledUpServerMessageEvent($skill->character->user, $skill->refresh()));

            if ($this->shouldUpdateCharacterAttackData($skill->baseSkill)) {
                $this->updateCharacterAttackDataCache($character);
            }

            if ($skill->level >= $skill->baseSkill->max_level) {
                $newXp = 0;
                break;
            }
        }

        $skill->update(['xp' => $newXp]);
    }


    protected function shouldUpdateCharacterAttackData(GameSkill $skill): bool
    {
        if (! is_null($skill->base_damage_mod_bonus_per_level)) {
            return false;
        }

        if (! is_null($skill->base_healing_mod_bonus_per_level)) {
            return false;
        }

        if (! is_null($skill->base_ac_mod_bonus_per_level)) {
            return false;
        }

        if (! is_null($skill->fight_time_out_mod_bonus_per_level)) {
            return false;
        }

        if (! is_null($skill->move_time_out_mod_bonus_per_level)) {
            return false;
        }

        return true;
    }

    protected function updateCharacterAttackDataCache(Character $character)
    {
        resolve(BuildCharacterAttackTypes::class)->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), resolve(CharacterSheetBaseInfoTransformer::class));

        $characterData = resolve(Manager::class)->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }
}
