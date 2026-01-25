<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\GameClass;
use App\Flare\Models\Survey;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ClassAttackValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Exception;

class CharacterSheetBaseInfoTransformer extends BaseTransformer
{
    private bool $ignoreReductions = false;

    protected array $defaultIncludes = [
        'inventory_count',
    ];

    public function setIgnoreReductions(bool $ignoreReductions): void
    {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * Gets the response data for the character sheet
     *
     * @throws Exception
     */
    public function transform(Character $character): array
    {
        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);
        $gameClass = GameClass::find($character->game_class_id);
        $factionLoyalty = $character->factionLoyalties()->where('is_pledged', '=', true)->first();

        return [
            'id' => $character->id,
            'user_id' => $character->user_id,
            'name' => $character->name,
            'class' => $gameClass->name,
            'class_id' => $gameClass->id,
            'race' => $character->race->name,
            'race_id' => $character->race->id,
            'to_hit_stat' => $character->class->to_hit_stat,
            'damage_stat' => $character->class->damage_stat,
            'level' => number_format($character->level),
            'max_level' => number_format($this->getMaxLevel($character)),
            'xp' => (int) $character->xp,
            'xp_next' => (int) $character->xp_next,
            'str_modded' => $characterStatBuilder->statMod('str'),
            'dur_modded' => $characterStatBuilder->statMod('dur'),
            'dex_modded' => $characterStatBuilder->statMod('dex'),
            'chr_modded' => $characterStatBuilder->statMod('chr'),
            'int_modded' => $characterStatBuilder->statMod('int'),
            'agi_modded' => $characterStatBuilder->statMod('agi'),
            'focus_modded' => $characterStatBuilder->statMod('focus'),
            'attack' => $characterStatBuilder->buildTotalAttack(),
            'health' => $characterStatBuilder->buildHealth(),
            'ac' => $characterStatBuilder->buildDefence(),
            'extra_action_chance' => (new ClassAttackValue($character))->buildAttackData(),
            'gold' => number_format($character->gold),
            'gold_dust' => number_format($character->gold_dust),
            'shards' => number_format($character->shards),
            'copper_coins' => number_format($character->copper_coins),
            'is_dead' => $character->is_dead,
            'can_craft' => $character->can_craft,
            'can_attack' => $character->can_attack,
            'can_spin' => $character->can_spin,
            'can_move' => $character->can_move,
            'can_engage_celestials' => $character->can_engage_celestials,
            'can_engage_celestials_again_at' => now()->diffInSeconds($character->can_engage_celestials_again_at),
            'can_attack_again_at' => now()->diffInSeconds($character->can_attack_again_at),
            'can_craft_again_at' => now()->diffInSeconds($character->can_craft_again_at),
            'can_spin_again_at' => now()->diffInSeconds($character->can_spin_again_at),
            'is_automation_running' => $character->currentAutomations()->where('character_id', $character->id)->get()->isNotEmpty(),
            'is_dwelve_running' => $character->currentAutomations()->where('character_id', $character->id)->where('type', AutomationType::DWELVE)->get()->isNotEmpty(),
            'automation_completed_at' => $this->getTimeLeftOnAutomation($character),
            'is_silenced' => $character->user->is_silenced,
            'can_talk_again_at' => $character->user->can_talk_again_at,
            'can_move_again_at' => now()->diffInSeconds($character->can_move_again_at),
            'force_name_change' => $character->force_name_change,
            'is_alchemy_locked' => $this->isAlchemyLocked($character),
            'can_use_work_bench' => false,
            'can_access_queen' => false,
            'can_access_hell_forged' => $character->map->gameMap->mapType()->isHell(),
            'can_access_purgatory_chains' => $character->map->gameMap->mapType()->isPurgatory(),
            'can_access_labyrinth_oracle' => $character->map->gameMap->mapType()->isLabyrinth(),
            'can_access_twisted_earth' => $character->map->gameMap->mapType()->isTwistedMemories(),
            'is_in_timeout' => ! is_null($character->user->timeout_until),
            'can_see_pledge_tab' => ! is_null($factionLoyalty),
            'pledged_to_faction_id' => ! is_null($factionLoyalty) ? $factionLoyalty->faction_id : null,
            'current_fame_tasks' => $this->getFactionTasks($factionLoyalty),
            'resurrection_chance' => $characterStatBuilder->buildResurrectionChance(),
            'is_showing_survey' => $character->user->is_showing_survey,
            'survey_id' => $character->user->is_showing_survey ? Survey::latest()->first()->id : null,
        ];
    }

    public function includeInventoryCount(Character $character)
    {
        return $this->item($character, new CharacterInventoryCountTransformer);
    }


    private function getFactionTasks(?FactionLoyalty $factionLoyalty = null): ?array
    {

        if (is_null($factionLoyalty)) {
            return null;
        }

        $factionLoyaltyNpc = $factionLoyalty->factionLoyaltyNpcs->where('currently_helping', true)->first();

        if (is_null($factionLoyaltyNpc)) {
            return null;
        }

        return array_values(collect($factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)->filter(function ($task) {
            return $task['type'] !== 'bounty';
        })->toArray());
    }

    private function getTimeLeftOnAutomation(Character $character)
    {
        $automation = $character->currentAutomations()->where('type', AutomationType::EXPLORING)->first();

        if (! is_null($automation)) {
            return now()->diffInSeconds($automation->completed_at);
        }

        return 0;
    }
}
