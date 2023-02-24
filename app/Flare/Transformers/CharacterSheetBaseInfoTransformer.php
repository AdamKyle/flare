<?php

namespace App\Flare\Transformers;

use Cache;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\AutomationType;
use App\Game\Skills\Values\SkillTypeValue;
use App\Flare\Models\Character;
use Facades\App\Flare\Transformers\DataSets\CharacterAttackData;

class CharacterSheetBaseInfoTransformer extends BaseTransformer {

    private bool $ignoreReductions = false;

    public function setIgnoreReductions(bool $ignoreReductions): void {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array {
        $characterStatBuilder         = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);
        $gameClass                    = GameClass::find($character->game_class_id);

        $baseStat = [
            'id'                          => $character->id,
            'user_id'                     => $character->user_id,
            'name'                        => $character->name,
            'class'                       => $gameClass->name,
            'class_id'                    => $gameClass->id,
            'race'                        => $character->race->name,
            'race_id'                     => $character->race->id,
            'inventory_max'               => $character->inventory_max,
            'inventory_count'             => $character->getInventoryCount(),
            'level'                       => number_format($character->level),
            'max_level'                   => number_format($this->getMaxLevel($character)),
            'xp'                          => (int) $character->xp,
            'xp_next'                     => (int) $character->xp_next,
            'str'                         => $character->str,
            'dur'                         => $character->dur,
            'dex'                         => $character->dex,
            'chr'                         => $character->chr,
            'int'                         => $character->int,
            'agi'                         => $character->agi,
            'focus'                       => $character->focus,
            'gold'                        => number_format($character->gold),
            'gold_dust'                   => number_format($character->gold_dust),
            'shards'                      => number_format($character->shards),
            'copper_coins'                => number_format($character->copper_coins),
            'is_dead'                     => $character->is_dead,
            'killed_in_pvp'               => $character->killed_in_pvp,
            'can_craft'                   => $character->can_craft,
            'can_attack'                  => $character->can_attack,
            'can_spin'                    => $character->can_spin,
            'is_mercenary_unlocked'       => $character->is_mercenary_unlocked,
            'can_engage_celestials'       => $character->can_engage_celestials,
            'can_engage_celestials_again_at' => now()->diffInSeconds($character->can_engage_celestials_again_at),
            'can_attack_again_at'         => now()->diffInSeconds($character->can_attack_again_at),
            'can_craft_again_at'          => now()->diffInSeconds($character->can_craft_again_at),
            'can_spin_again_at'           => now()->diffInSeconds($character->can_spin_again_at),
            'is_automation_running'       => $character->currentAutomations()->where('type', AutomationType::EXPLORING)->get()->isNotEmpty(),
            'automation_completed_at'     => $this->getTimeLeftOnAutomation($character),
            'is_silenced'                 => $character->user->is_silenced,
            'can_talk_again_at'           => $character->user->can_talk_again_at,
            'can_move'                    => $character->can_move,
            'can_move_again_at'           => now()->diffInSeconds($character->can_move_again_at),
            'force_name_change'           => $character->force_name_change,
            'is_alchemy_locked'           => $this->isAlchemyLocked($character),
            'can_use_work_bench'          => false,
            'can_access_queen'            => false,
            'can_access_hell_forged'      => false,
            'can_access_purgatory_chains' => false,
            'is_in_timeout'               => !is_null($character->user->timeout_until),
            'base_position' => [
              'x' => $character->map->character_position_x,
              'y' => $character->map->character_position_y,
              'game_map_id' => $character->map->game_map_id,
            ],
        ];

        $attackData = CharacterAttackData::attackData($character, $characterStatBuilder);

        return array_merge($baseStat, $attackData);
    }

    public function isAlchemyLocked(Character $character): bool {
        return Skill::where('character_id', $character->id)->where('game_skill_id', GameSkill::where('type', SkillTypeValue::ALCHEMY)->first()->id)->first()->is_locked;
    }

    protected function getTimeLeftOnAutomation(Character $character) {
        $automation = $character->currentAutomations()->where('type', AutomationType::EXPLORING)->first();

        if (!is_null($automation)) {
            return now()->diffInSeconds($automation->completed_at);
        }

        return 0;
    }
}
