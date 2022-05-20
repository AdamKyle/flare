<?php

namespace App\Flare\Listeners;

use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Flare\Calculators\SkillXPCalculator;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class UpdateSkillListener
{

    private $skillService;

    public function __construct(SkillService $skillService) {
        $this->skillService = $skillService;
    }

    /**
     * Handle the event.
     *
     * @param  UpdateSkillEvent $event
     * @return void
     */
    public function handle(UpdateSkillEvent $event) {
        if ($event->skill->max_level <= $event->skill->level) {
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

    protected function getSkillXp(Skill $skill, Monster $monster = null): float|int {
        $gameMap = $skill->character->map->gameMap;

        $skillXP = SkillXPCalculator::fetchSkillXP($skill, $monster);

        if (!is_null($gameMap->skill_training_bonus)) {
            $skillXP = $skillXP + $skillXP * $gameMap->skill_training_bonus;
        }

        return $skillXP;
    }

    protected function updateSkill(Skill $skill, int $skillXP) {
        $skill->update([
            'xp' => $skill->xp + $skillXP,
        ]);

        $skill = $skill->refresh();

        if ($skill->xp >= $skill->xp_max) {
            $level = $skill->level + 1;

            $skill->update([
                'level'              => $level,
                'xp_max'             => $skill->can_train ? $level * 100 : rand(100, 350),
                'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                'skill_bonus'        => $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level,
                'xp'                 => 0,
            ]);

            $character = $skill->character->refresh();

            event(new SkillLeveledUpServerMessageEvent($skill->character->user, $skill->refresh()));

            if ($this->shouldUpdateCharacterAttackData($skill->baseSkill)) {
                $this->updateCharacterAttackDataCache($character);
            }
        }
    }

    protected function shouldUpdateCharacterAttackData(GameSkill $skill): bool {
        if (!is_null($skill->base_damage_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->base_healing_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->base_ac_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->fight_time_out_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->move_time_out_mod_bonus_per_level)) {
            return false;
        }

        return true;
    }

    protected function updateCharacterAttackDataCache(Character $character) {
        resolve(BuildCharacterAttackTypes::class)->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), resolve(CharacterSheetBaseInfoTransformer::class));

        $characterData = resolve(Manager::class)->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }
}
