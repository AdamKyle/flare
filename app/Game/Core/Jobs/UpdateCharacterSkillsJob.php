<?php

namespace App\Game\Core\Jobs;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\UseItemService;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Skills\Services\SkillService;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use League\Fractal\Resource\Item as ResourceItem;

class UpdateCharacterSkillsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var int $slotId
     */
    private $gameSkillIds;


    public function __construct(Character $character, array $gameSkillIds)
    {
        $this->character    = $character;
        $this->gameSkillIds = $gameSkillIds;
    }

    public function handle(SkillService $skillService) {
        ini_set('memory_limit','3G');

        $skills = Skill::where('character_id', $this->character->id)->whereIn('game_skill_id', $this->gameSkillIds)->get();

        foreach ($skills as $skill) {
            $skill->update([
                'xp_max' => $skill->level * 100,
            ]);

            $this->updateSkill($skill->refresh(), $skillService);
        }

        $this->updateCharacterAttackDataCache($this->character->refresh());
    }



    protected function updateSkill(Skill $skill, SkillService $skillService) {
        if ($skill->xp >= $skill->xp_max) {
            $level = $skill->level + 1;

            $skill->update([
                'level'              => $level,
                'xp_max'             => $skill->can_train ? $skill->level * 100 : rand(100, 350),
                'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                'skill_bonus'        => $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level,
                'xp'                 => 0,
            ]);
        }

        $skillService->updateSkills($skill->character->refresh());
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
